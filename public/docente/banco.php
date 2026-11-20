<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'evaluaciones.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();
const BANCO_MAX_OPCIONES = 6;

$errores = [];
$valores = [
    'tipo' => 'seleccion_multiple',
    'enunciado' => '',
    'puntaje' => '1',
    'retroalimentacion' => '',
    'opciones' => array_fill(0, 4, ''),
    'correcta' => '0',
    'correcta_vf' => 'v',
    'respuesta_corta' => '',
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCsrfFormulario();
        $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
        if ($accion === 'eliminar') {
            $preguntaId = leerId($_POST['pregunta'] ?? null);
            if ($preguntaId === null) {
                redirigirConMensaje('banco.php', 'La pregunta solicitada no es válida.', 'danger');
            }
            $borrar = $db->prepare('DELETE FROM banco_preguntas WHERE id = ? AND docente_id = ?');
            $borrar->execute([$preguntaId, $docenteId]);
            redirigirConMensaje('banco.php', $borrar->rowCount() === 1 ? 'Pregunta eliminada del banco.' : 'La pregunta no existe o no te pertenece.', $borrar->rowCount() === 1 ? 'success' : 'danger');
        }

        if ($accion === 'usar') {
            $preguntaId = leerId($_POST['pregunta'] ?? null);
            $evaluacionId = leerId($_POST['evaluacion'] ?? null);
            if ($preguntaId === null || $evaluacionId === null) {
                redirigirConMensaje('banco.php', 'Selecciona una pregunta y una evaluación válidas.', 'danger');
            }
            $evaluacion = buscarEvaluacionPropia($db, $evaluacionId, $docenteId);
            $consulta = $db->prepare('SELECT * FROM banco_preguntas WHERE id = ? AND docente_id = ?');
            $consulta->execute([$preguntaId, $docenteId]);
            $pregunta = $consulta->fetch();
            if ($evaluacion === null || $pregunta === false) {
                redirigirConMensaje('banco.php', 'La evaluación o la pregunta no existe o no te pertenece.', 'danger');
            }
            if (evaluacionTieneIntentos($db, $evaluacionId)) {
                redirigirConMensaje('banco.php', 'No se pueden agregar preguntas a una evaluación que ya tiene intentos.', 'warning');
            }

            $opciones = json_decode((string)($pregunta['opciones_json'] ?? '[]'), true);
            if (!is_array($opciones)) {
                throw new RuntimeException('Las opciones de la pregunta guardada no tienen un formato válido.');
            }
            $db->beginTransaction();
            $posicion = $db->prepare('SELECT COALESCE(MAX(posicion), 0) + 1 FROM preguntas WHERE evaluacion_id = ?');
            $posicion->execute([$evaluacionId]);
            $db->prepare(
                'INSERT INTO preguntas (evaluacion_id, tipo, enunciado, respuesta_corta, puntaje, retroalimentacion, posicion)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $evaluacionId, $pregunta['tipo'], $pregunta['enunciado'], $pregunta['respuesta_corta'],
                $pregunta['puntaje'], $pregunta['retroalimentacion'], (int)$posicion->fetchColumn(),
            ]);
            $nuevaPreguntaId = (int)$db->lastInsertId();
            $insertarOpcion = $db->prepare('INSERT INTO opciones (pregunta_id, texto, correcta, posicion) VALUES (?, ?, ?, ?)');
            foreach ($opciones as $indice => $opcion) {
                if (!is_array($opcion) || !is_string($opcion['texto'] ?? null)) {
                    throw new RuntimeException('Una opción guardada no tiene un formato válido.');
                }
                $insertarOpcion->execute([
                    $nuevaPreguntaId, $opcion['texto'], !empty($opcion['correcta']) ? 1 : 0, $indice + 1,
                ]);
            }
            registrarAuditoria($db, $docenteId, $evaluacionId, 'pregunta_desde_banco', [
                'banco_pregunta_id' => $preguntaId, 'pregunta_id' => $nuevaPreguntaId,
            ]);
            $db->commit();
            redirigirConMensaje('banco.php', 'Pregunta agregada a «' . $evaluacion['titulo'] . '».');
        }

        if ($accion !== 'crear') {
            redirigirConMensaje('banco.php', 'La acción solicitada no es válida.', 'danger');
        }

        $texto = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
        $valores['tipo'] = $texto('tipo');
        $valores['enunciado'] = $texto('enunciado');
        $valores['puntaje'] = $texto('puntaje');
        $valores['retroalimentacion'] = $texto('retroalimentacion');
        $valores['correcta'] = $texto('correcta');
        $valores['correcta_vf'] = $texto('correcta_vf');
        $valores['respuesta_corta'] = $texto('respuesta_corta');
        $entradas = is_array($_POST['opciones'] ?? null) ? array_slice($_POST['opciones'], 0, BANCO_MAX_OPCIONES) : [];
        $valores['opciones'] = array_pad(array_map(static fn($opcion): string => is_string($opcion) ? trim($opcion) : '', $entradas), 4, '');

        foreach (['enunciado', 'retroalimentacion', 'respuesta_corta'] as $campo) {
            if (!mb_check_encoding($valores[$campo], 'UTF-8')) {
                $valores[$campo] = mb_scrub($valores[$campo], 'UTF-8');
                $errores[$campo] = 'El texto contiene caracteres no válidos.';
            }
        }
        if (!isset(TIPOS_PREGUNTA[$valores['tipo']])) {
            $errores['tipo'] = 'Elige un tipo de pregunta.';
            $valores['tipo'] = 'seleccion_multiple';
        }
        if ($valores['enunciado'] === '') {
            $errores['enunciado'] = 'Escribe el enunciado de la pregunta.';
        } elseif (mb_strlen($valores['enunciado'], 'UTF-8') > 5000) {
            $errores['enunciado'] = 'El enunciado admite como máximo 5000 caracteres.';
        }
        $puntaje = filter_var(str_replace(',', '.', $valores['puntaje']), FILTER_VALIDATE_FLOAT);
        if ($puntaje === false || $puntaje <= 0 || $puntaje > 1000) {
            $errores['puntaje'] = 'El puntaje debe ser un número mayor que 0 (máximo 1000).';
        }
        if (mb_strlen($valores['retroalimentacion'], 'UTF-8') > 2000) {
            $errores['retroalimentacion'] = 'La retroalimentación admite como máximo 2000 caracteres.';
        }

        $opciones = [];
        $respuestaCorta = null;
        if ($valores['tipo'] === 'seleccion_multiple') {
            $correcta = filter_var($valores['correcta'], FILTER_VALIDATE_INT);
            foreach ($valores['opciones'] as $indice => $textoOpcion) {
                if ($textoOpcion === '') {
                    continue;
                }
                if (!mb_check_encoding($textoOpcion, 'UTF-8') || mb_strlen($textoOpcion, 'UTF-8') > 1000) {
                    $errores['opciones'] = 'Cada opción admite como máximo 1000 caracteres.';
                }
                $opciones[] = ['texto' => $textoOpcion, 'correcta' => $indice === $correcta];
            }
            if (count($opciones) < 2) {
                $errores['opciones'] = 'Escribe al menos 2 opciones.';
            } elseif (count(array_filter($opciones, static fn(array $opcion): bool => $opcion['correcta'])) !== 1) {
                $errores['opciones'] = 'Marca cuál de las opciones escritas es la correcta.';
            }
        } elseif ($valores['tipo'] === 'verdadero_falso') {
            if (!in_array($valores['correcta_vf'], ['v', 'f'], true)) {
                $errores['correcta_vf'] = 'Indica si la afirmación es verdadera o falsa.';
            }
            $opciones = [
                ['texto' => 'Verdadero', 'correcta' => $valores['correcta_vf'] === 'v'],
                ['texto' => 'Falso', 'correcta' => $valores['correcta_vf'] === 'f'],
            ];
        } else {
            if ($valores['respuesta_corta'] === '') {
                $errores['respuesta_corta'] = 'Escribe la respuesta esperada.';
            } elseif (mb_strlen($valores['respuesta_corta'], 'UTF-8') > 500) {
                $errores['respuesta_corta'] = 'La respuesta esperada admite como máximo 500 caracteres.';
            }
            $respuestaCorta = $valores['respuesta_corta'];
        }

        if (!$errores) {
            $db->prepare(
                'INSERT INTO banco_preguntas (docente_id, tipo, enunciado, respuesta_corta, opciones_json, puntaje, retroalimentacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $docenteId, $valores['tipo'], $valores['enunciado'], $respuestaCorta,
                $opciones ? json_encode($opciones, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
                number_format(round($puntaje, 2), 2, '.', ''),
                $valores['retroalimentacion'] === '' ? null : $valores['retroalimentacion'],
            ]);
            redirigirConMensaje('banco.php', 'Pregunta guardada en tu banco.');
        }
        http_response_code(422);
    } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        header('Allow: GET, POST');
        exit('Método no permitido.');
    }

    $consulta = $db->prepare('SELECT * FROM banco_preguntas WHERE docente_id = ? ORDER BY creado_en DESC, id DESC');
    $consulta->execute([$docenteId]);
    $preguntas = $consulta->fetchAll();
    $consulta = $db->prepare(
        'SELECT e.id, e.titulo FROM evaluaciones e WHERE e.docente_id = ?
         AND NOT EXISTS (SELECT 1 FROM intentos i WHERE i.evaluacion_id = e.id)
         ORDER BY e.creado_en DESC, e.id DESC'
    );
    $consulta->execute([$docenteId]);
    $evaluacionesDisponibles = $consulta->fetchAll();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error al gestionar el banco de preguntas: ' . $exception);
    http_response_code(500);
    exit('No se pudo gestionar el banco de preguntas. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Banco de preguntas';
$subtitulo = 'Guarda preguntas y reutilízalas en tus evaluaciones.';
$seccion = 'banco';
$scripts = ['../assets/js/preguntas.js'];
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_banco.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
