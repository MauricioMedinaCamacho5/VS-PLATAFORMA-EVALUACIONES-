<?php
declare(strict_types=1);

// Presentación de evaluaciones: intentos, autoguardado y calificación.
// Regla de seguridad: las columnas opciones.correcta y preguntas.respuesta_corta solo se leen
// en calificarIntento() (en el servidor) y nunca se envían al navegador.

require_once __DIR__ . DIRECTORY_SEPARATOR . 'evaluaciones.php';

/** Segundos de margen tras el vencimiento para aceptar un envío (latencia de red). */
const GRACIA_SEGUNDOS = 30;
const LONGITUD_MAXIMA_RESPUESTA = 2000;

/** Intento del estudiante con los datos de su evaluación y los segundos que le quedan. */
function intentoDelEstudiante(PDO $db, int $intentoId, int $estudianteId, bool $bloquear = false): ?array
{
    $consulta = $db->prepare(
        'SELECT i.*, TIMESTAMPDIFF(SECOND, NOW(), i.vence_en) AS segundos_restantes,
                e.titulo, e.nota_minima, e.mostrar_retroalimentacion, e.intentos_permitidos, e.tiempo_minutos
         FROM intentos i JOIN evaluaciones e ON e.id = i.evaluacion_id
         WHERE i.id = ? AND i.estudiante_id = ?' . ($bloquear ? ' FOR UPDATE' : '')
    );
    $consulta->execute([$intentoId, $estudianteId]);
    $fila = $consulta->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Inicia un intento nuevo o devuelve el que está en curso.
 * @return array{0: ?int, 1: ?string} [id del intento, mensaje de error]
 */
function iniciarOContinuarIntento(PDO $db, int $estudianteId, int $evaluacionId): array
{
    finalizarIntentosVencidos($db, $estudianteId);
    $evaluacion = evaluacionesDelEstudiante($db, $estudianteId, $evaluacionId)[0] ?? null;
    if ($evaluacion === null) {
        return [null, 'Esa evaluación no existe o no está asignada a tus clases.'];
    }
    if ($evaluacion['intento_en_curso'] !== null) {
        return [(int)$evaluacion['intento_en_curso'], null];
    }
    if ($evaluacion['estado'] !== 'abierta') {
        return [null, $evaluacion['estado'] === 'proxima' ? 'La evaluación todavía no abre.' : 'La evaluación ya cerró.'];
    }
    if ((int)$evaluacion['total_preguntas'] === 0) {
        return [null, 'El docente aún no ha añadido preguntas a esta evaluación.'];
    }

    $db->beginTransaction();
    try {
        // Bloquea al estudiante para que dos pestañas no creen intentos a la vez.
        $db->prepare('SELECT id FROM usuarios WHERE id = ? FOR UPDATE')->execute([$estudianteId]);

        $consulta = $db->prepare(
            "SELECT COUNT(*) AS usados, MAX(CASE WHEN estado = 'en_curso' THEN id END) AS en_curso
             FROM intentos WHERE evaluacion_id = ? AND estudiante_id = ?"
        );
        $consulta->execute([$evaluacionId, $estudianteId]);
        $estado = $consulta->fetch();
        if ($estado['en_curso'] !== null) {
            $db->commit();
            return [(int)$estado['en_curso'], null];
        }
        if ((int)$estado['usados'] >= (int)$evaluacion['intentos_permitidos']) {
            $db->rollBack();
            return [null, 'Ya usaste todos los intentos permitidos.'];
        }

        // Orden de preguntas (y de opciones) fijado al iniciar, para que no cambie al recargar.
        $preguntas = $db->prepare('SELECT id FROM preguntas WHERE evaluacion_id = ? ORDER BY posicion, id');
        $preguntas->execute([$evaluacionId]);
        $ordenPreguntas = array_map('intval', $preguntas->fetchAll(PDO::FETCH_COLUMN));
        $ordenOpciones = [];
        if ((int)$evaluacion['orden_aleatorio'] === 1) {
            mezclar($ordenPreguntas);
            $opciones = $db->prepare('SELECT o.pregunta_id, o.id FROM opciones o JOIN preguntas p ON p.id = o.pregunta_id
                                      WHERE p.evaluacion_id = ? AND p.tipo = \'seleccion_multiple\' ORDER BY o.posicion, o.id');
            $opciones->execute([$evaluacionId]);
            foreach ($opciones->fetchAll() as $fila) {
                $ordenOpciones[(int)$fila['pregunta_id']][] = (int)$fila['id'];
            }
            foreach ($ordenOpciones as &$lista) {
                mezclar($lista);
            }
            unset($lista);
        }

        // El intento vence al acabar el tiempo límite o al cerrar la evaluación, lo que ocurra primero.
        $db->prepare(
            'INSERT INTO intentos (evaluacion_id, estudiante_id, numero, vence_en, orden_json)
             SELECT e.id, ?, ?, LEAST(NOW() + INTERVAL e.tiempo_minutos MINUTE, e.cierre), ?
             FROM evaluaciones e WHERE e.id = ?'
        )->execute([
            $estudianteId, (int)$estado['usados'] + 1,
            json_encode(['preguntas' => $ordenPreguntas, 'opciones' => $ordenOpciones]), $evaluacionId,
        ]);
        $intentoId = (int)$db->lastInsertId();
        registrarAuditoria($db, $estudianteId, $evaluacionId, 'intento_iniciar', ['intento_id' => $intentoId]);
        $db->commit();
        return [$intentoId, null];
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

/** Mezcla con random_int (criptográficamente seguro), sin depender de shuffle(). */
function mezclar(array &$lista): void
{
    for ($i = count($lista) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$lista[$i], $lista[$j]] = [$lista[$j], $lista[$i]];
    }
}

/**
 * Preguntas del intento para mostrarlas al estudiante, con sus respuestas guardadas.
 * NO incluye cuál opción es correcta ni la respuesta esperada.
 */
function preguntasParaPresentar(PDO $db, array $intento): array
{
    $consulta = $db->prepare('SELECT id, tipo, enunciado, puntaje FROM preguntas WHERE evaluacion_id = ?');
    $consulta->execute([$intento['evaluacion_id']]);
    $porId = [];
    foreach ($consulta->fetchAll() as $fila) {
        $fila['opciones'] = [];
        $fila['respuesta_opcion'] = null;
        $fila['respuesta_texto'] = '';
        $porId[(int)$fila['id']] = $fila;
    }

    $consulta = $db->prepare(
        'SELECT o.id, o.pregunta_id, o.texto FROM opciones o JOIN preguntas p ON p.id = o.pregunta_id
         WHERE p.evaluacion_id = ? ORDER BY o.posicion, o.id'
    );
    $consulta->execute([$intento['evaluacion_id']]);
    foreach ($consulta->fetchAll() as $opcion) {
        $porId[(int)$opcion['pregunta_id']]['opciones'][(int)$opcion['id']] = ['id' => (int)$opcion['id'], 'texto' => $opcion['texto']];
    }

    $consulta = $db->prepare('SELECT pregunta_id, opcion_id, texto FROM respuestas WHERE intento_id = ?');
    $consulta->execute([$intento['id']]);
    foreach ($consulta->fetchAll() as $respuesta) {
        $pid = (int)$respuesta['pregunta_id'];
        if (isset($porId[$pid])) {
            $porId[$pid]['respuesta_opcion'] = $respuesta['opcion_id'] !== null ? (int)$respuesta['opcion_id'] : null;
            $porId[$pid]['respuesta_texto'] = (string)($respuesta['texto'] ?? '');
        }
    }

    $orden = json_decode((string)($intento['orden_json'] ?? ''), true);
    $ordenPreguntas = is_array($orden['preguntas'] ?? null) ? $orden['preguntas'] : array_keys($porId);
    $resultado = [];
    foreach ($ordenPreguntas as $pid) {
        if (!isset($porId[(int)$pid])) {
            continue;
        }
        $pregunta = $porId[(int)$pid];
        $ordenOpciones = $orden['opciones'][(string)$pid] ?? $orden['opciones'][(int)$pid] ?? null;
        if (is_array($ordenOpciones)) {
            $ordenadas = [];
            foreach ($ordenOpciones as $oid) {
                if (isset($pregunta['opciones'][(int)$oid])) {
                    $ordenadas[] = $pregunta['opciones'][(int)$oid];
                }
            }
            $pregunta['opciones'] = $ordenadas;
        } else {
            $pregunta['opciones'] = array_values($pregunta['opciones']);
        }
        $resultado[] = $pregunta;
        unset($porId[(int)$pid]);
    }
    // Preguntas añadidas después de iniciar (no debería pasar: se bloquea la edición con intentos).
    foreach ($porId as $pregunta) {
        $pregunta['opciones'] = array_values($pregunta['opciones']);
        $resultado[] = $pregunta;
    }
    return $resultado;
}

/**
 * Guarda las respuestas recibidas ([pregunta_id => opcion_id | texto]).
 * Ignora preguntas u opciones que no pertenezcan a la evaluación del intento.
 */
function guardarRespuestas(PDO $db, array $intento, mixed $respuestas): int
{
    if (!is_array($respuestas)) {
        return 0;
    }
    $consulta = $db->prepare('SELECT id, tipo FROM preguntas WHERE evaluacion_id = ?');
    $consulta->execute([$intento['evaluacion_id']]);
    $tipos = [];
    foreach ($consulta->fetchAll() as $fila) {
        $tipos[(int)$fila['id']] = $fila['tipo'];
    }
    $consulta = $db->prepare('SELECT o.id, o.pregunta_id FROM opciones o JOIN preguntas p ON p.id = o.pregunta_id WHERE p.evaluacion_id = ?');
    $consulta->execute([$intento['evaluacion_id']]);
    $opcionesValidas = [];
    foreach ($consulta->fetchAll() as $fila) {
        $opcionesValidas[(int)$fila['id']] = (int)$fila['pregunta_id'];
    }

    $guardar = $db->prepare(
        'INSERT INTO respuestas (intento_id, pregunta_id, opcion_id, texto) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE opcion_id = VALUES(opcion_id), texto = VALUES(texto)'
    );
    $guardadas = 0;
    foreach ($respuestas as $clave => $valor) {
        $preguntaId = leerId($clave);
        if ($preguntaId === null || !isset($tipos[$preguntaId]) || (!is_string($valor) && !is_int($valor))) {
            continue;
        }
        if ($tipos[$preguntaId] === 'respuesta_corta') {
            $texto = trim((string)$valor);
            if (!mb_check_encoding($texto, 'UTF-8')) {
                continue;
            }
            $texto = mb_substr($texto, 0, LONGITUD_MAXIMA_RESPUESTA, 'UTF-8');
            $guardar->execute([$intento['id'], $preguntaId, null, $texto === '' ? null : $texto]);
        } else {
            $opcionId = leerId($valor);
            if ($opcionId === null || ($opcionesValidas[$opcionId] ?? null) !== $preguntaId) {
                continue;
            }
            $guardar->execute([$intento['id'], $preguntaId, $opcionId, null]);
        }
        $guardadas++;
    }
    return $guardadas;
}

/** Minúsculas, sin tildes ni espacios repetidos: "  Bogotá D.C. " y "bogota d.c." son iguales. */
function normalizarRespuesta(string $texto): string
{
    $texto = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $texto) ?? ''), 'UTF-8');
    return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u']);
}

/** Califica en el servidor y cierra el intento. Debe llamarse dentro de una transacción. */
function calificarIntento(PDO $db, array $intento): float
{
    $consulta = $db->prepare(
        'SELECT p.id, p.tipo, p.puntaje, p.respuesta_corta, r.opcion_id, r.texto, o.correcta
         FROM preguntas p
         LEFT JOIN respuestas r ON r.pregunta_id = p.id AND r.intento_id = ?
         LEFT JOIN opciones o ON o.id = r.opcion_id AND o.pregunta_id = p.id
         WHERE p.evaluacion_id = ?'
    );
    $consulta->execute([$intento['id'], $intento['evaluacion_id']]);
    $puntuar = $db->prepare(
        'INSERT INTO respuestas (intento_id, pregunta_id, puntaje_obtenido, revisada_en) VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE puntaje_obtenido = VALUES(puntaje_obtenido), revisada_en = NOW()'
    );
    $total = 0.0;
    $obtenido = 0.0;
    foreach ($consulta->fetchAll() as $pregunta) {
        $puntaje = (float)$pregunta['puntaje'];
        $total += $puntaje;
        $correcta = $pregunta['tipo'] === 'respuesta_corta'
            ? $pregunta['texto'] !== null && $pregunta['respuesta_corta'] !== null
              && normalizarRespuesta((string)$pregunta['texto']) === normalizarRespuesta((string)$pregunta['respuesta_corta'])
            : (int)$pregunta['correcta'] === 1;
        $puntos = $correcta ? $puntaje : 0.0;
        $obtenido += $puntos;
        $puntuar->execute([$intento['id'], $pregunta['id'], $puntos]);
    }
    $nota = $total > 0 ? round($obtenido / $total * NOTA_MAXIMA, 2) : 0.0;
    $db->prepare("UPDATE intentos SET estado = 'calificado', enviado_en = NOW(), nota = ? WHERE id = ? AND estado = 'en_curso'")
        ->execute([$nota, $intento['id']]);
    return $nota;
}

/**
 * Envía el intento: guarda las últimas respuestas (si llegan a tiempo) y lo califica.
 * @return array{0: bool, 1: string} [enviado ahora, mensaje]
 */
function enviarIntento(PDO $db, int $intentoId, int $estudianteId, mixed $respuestas, bool $automatico = false): array
{
    $db->beginTransaction();
    try {
        $intento = intentoDelEstudiante($db, $intentoId, $estudianteId, true);
        if ($intento === null) {
            $db->rollBack();
            return [false, 'El intento no existe.'];
        }
        if ($intento['estado'] !== 'en_curso') {
            $db->rollBack();
            return [false, 'Este intento ya se había enviado.'];
        }
        $aTiempo = (int)$intento['segundos_restantes'] >= -GRACIA_SEGUNDOS;
        if ($respuestas !== null && $aTiempo) {
            guardarRespuestas($db, $intento, $respuestas);
        }
        $nota = calificarIntento($db, $intento);
        registrarAuditoria($db, $estudianteId, (int)$intento['evaluacion_id'], 'intento_enviar', [
            'intento_id' => $intentoId, 'nota' => $nota, 'automatico' => $automatico || !$aTiempo,
        ]);
        $db->commit();
        return [true, $aTiempo ? 'Evaluación enviada.' : 'El tiempo se agotó: se calificaron las respuestas guardadas.'];
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

/** Califica los intentos del estudiante que vencieron sin enviarse (cerró la pestaña, se fue la luz...). */
function finalizarIntentosVencidos(PDO $db, int $estudianteId): void
{
    $consulta = $db->prepare(
        "SELECT id FROM intentos WHERE estudiante_id = ? AND estado = 'en_curso'
         AND vence_en < NOW() - INTERVAL " . GRACIA_SEGUNDOS . ' SECOND'
    );
    $consulta->execute([$estudianteId]);
    foreach ($consulta->fetchAll(PDO::FETCH_COLUMN) as $intentoId) {
        enviarIntento($db, (int)$intentoId, $estudianteId, null, true);
    }
}

/** Detalle del resultado para el estudiante: su respuesta, si acertó y la retroalimentación. Sin la respuesta correcta. */
function detalleResultado(PDO $db, array $intento): array
{
    $consulta = $db->prepare(
        'SELECT p.id, p.tipo, p.enunciado, p.puntaje, p.retroalimentacion, p.posicion,
                r.puntaje_obtenido, r.texto AS respuesta_texto, o.texto AS respuesta_opcion
         FROM preguntas p
         LEFT JOIN respuestas r ON r.pregunta_id = p.id AND r.intento_id = ?
         LEFT JOIN opciones o ON o.id = r.opcion_id
         WHERE p.evaluacion_id = ? ORDER BY p.posicion, p.id'
    );
    $consulta->execute([$intento['id'], $intento['evaluacion_id']]);
    return $consulta->fetchAll();
}
