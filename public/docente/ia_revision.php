<?php
declare(strict_types=1);

// Revisión de las preguntas generadas por IA: editar, aprobar, descartar, generar más y guardar.
// Solo las aprobadas se guardan en la evaluación (y, si el docente lo marca, en su banco de preguntas).

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ia.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

$metodo = $_SERVER['REQUEST_METHOD'];
$borradorId = $metodo === 'POST' ? ($_POST['borrador'] ?? null) : ($_GET['borrador'] ?? null);
$borrador = obtenerBorradorIa($borradorId);
if ($borrador === null) {
    redirigirConMensaje('ia.php', 'Ese borrador ya no existe (se guardó, se canceló o caducó la sesión). Genera las preguntas de nuevo.', 'warning');
}
$volver = 'ia_revision.php?borrador=' . $borradorId;

/** La evaluación debe seguir siendo del docente y no tener intentos. */
function evaluacionDestino(PDO $db, int $evaluacionId, int $docenteId): ?array
{
    $evaluacion = buscarEvaluacionPropia($db, $evaluacionId, $docenteId);
    return $evaluacion !== null && !evaluacionTieneIntentos($db, $evaluacionId) ? $evaluacion : null;
}

$erroresEdicion = [];
$editando = null;
try {
    $evaluacion = evaluacionDestino($db, (int)$borrador['evaluacion_id'], $docenteId);
    if ($evaluacion === null) {
        unset($_SESSION['ia_borradores'][$borradorId]);
        redirigirConMensaje('ia.php', 'La evaluación de destino ya no existe o ya tiene intentos, así que no se le pueden añadir preguntas.', 'warning');
    }

    if ($metodo === 'POST') {
        verificarCsrfFormulario();
        $accion = $_POST['accion'] ?? '';
        $numero = filter_var($_POST['pregunta'] ?? null, FILTER_VALIDATE_INT);
        $pregunta = $numero !== false && isset($borrador['preguntas'][$numero]) ? $borrador['preguntas'][$numero] : null;
        $estado = static function (int $numero, string $nuevo) use ($borradorId): void {
            $_SESSION['ia_borradores'][$borradorId]['preguntas'][$numero]['estado'] = $nuevo;
        };

        if (in_array($accion, ['aprobar', 'descartar', 'restaurar'], true)) {
            if ($pregunta === null) {
                redirigirConMensaje($volver, 'Esa pregunta no existe.', 'danger');
            }
            $estado($numero, ['aprobar' => 'aprobada', 'descartar' => 'descartada', 'restaurar' => 'pendiente'][$accion]);
            header('Location: ' . $volver . '#pregunta-' . $numero, true, 303);
            exit;
        }

        if ($accion === 'aprobar_todas') {
            $total = 0;
            foreach ($borrador['preguntas'] as $n => $p) {
                if ($p['estado'] === 'pendiente') {
                    $estado($n, 'aprobada');
                    $total++;
                }
            }
            redirigirConMensaje($volver, $total > 0 ? "Se aprobaron {$total} pregunta(s) pendientes." : 'No había preguntas pendientes.', $total > 0 ? 'success' : 'info');
        }

        if ($accion === 'editar') {
            if ($pregunta === null) {
                redirigirConMensaje($volver, 'Esa pregunta no existe.', 'danger');
            }
            $texto = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
            $puntaje = filter_var(str_replace(',', '.', $texto('puntaje')), FILTER_VALIDATE_FLOAT);
            if ($puntaje === false || $puntaje <= 0 || $puntaje > 1000) {
                $erroresEdicion['puntaje'] = 'El puntaje debe ser mayor que 0 (máximo 1000).';
            }
            $opciones = is_array($_POST['opciones'] ?? null) ? array_values(array_filter(
                array_map(static fn($o): string => is_string($o) ? $o : '', array_slice($_POST['opciones'], 0, 6)),
                static fn(string $o): bool => trim($o) !== ''
            )) : [];
            // El índice de la correcta se calcula sobre las opciones no vacías, en el mismo orden.
            $correcta = filter_var($_POST['correcta'] ?? null, FILTER_VALIDATE_INT);
            if ($pregunta['tipo'] === 'seleccion_multiple' && $correcta !== false) {
                $posicionesNoVacias = array_keys(array_filter(
                    array_slice(is_array($_POST['opciones'] ?? null) ? $_POST['opciones'] : [], 0, 6),
                    static fn($o): bool => is_string($o) && trim($o) !== ''
                ));
                $correcta = array_search($correcta, $posicionesNoVacias, true);
            }
            [$editada, $motivo] = validarPreguntaIa([
                'tipo' => $pregunta['tipo'], 'enunciado' => $texto('enunciado'), 'opciones' => $opciones,
                'correcta' => $correcta === false ? null : (int)$correcta,
                'respuesta_corta' => $texto('respuesta_corta'), 'retroalimentacion' => $texto('retroalimentacion'),
                'dificultad' => $texto('dificultad'),
            ], $puntaje === false ? 1.0 : round($puntaje, 2), $pregunta['dificultad']);
            if ($editada === null) {
                $erroresEdicion['general'] = 'No se pudo guardar: ' . $motivo . '.';
            }
            if (!$erroresEdicion) {
                $_SESSION['ia_borradores'][$borradorId]['preguntas'][$numero] = $editada + ['id' => $numero, 'estado' => 'aprobada', 'editada' => true];
                redirigirConMensaje($volver . '#pregunta-' . $numero, 'Pregunta editada y aprobada.');
            }
            $editando = $numero;
            $borrador = obtenerBorradorIa($borradorId);
            http_response_code(422);
        }

        if ($accion === 'generar_mas') {
            if (($error = iaErrorConfiguracion()) !== null) {
                redirigirConMensaje($volver, $error, 'danger');
            }
            $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => IA_MAX_PREGUNTAS]]);
            $disponibles = IA_MAX_POR_BORRADOR - count($borrador['preguntas']);
            if ($cantidad === false) {
                redirigirConMensaje($volver, 'Indica cuántas preguntas más quieres (1 a ' . IA_MAX_PREGUNTAS . ').', 'danger');
            }
            if ($disponibles <= 0) {
                redirigirConMensaje($volver, 'Este borrador llegó al máximo de ' . IA_MAX_POR_BORRADOR . ' preguntas. Guarda las aprobadas y empieza otro.', 'warning');
            }
            $parametros = ['cantidad' => min($cantidad, $disponibles)] + $borrador['parametros'];
            // Se envían todos los enunciados existentes (también descartados) para que no se repitan.
            $existentes = array_column($borrador['preguntas'], 'enunciado');
            $resultado = generarPreguntasIa($parametros, $borrador['fuente'], $existentes);
            if ($resultado['error'] !== null) {
                redirigirConMensaje($volver, $resultado['error'], 'danger');
            }
            agregarPreguntasBorrador($borradorId, $resultado['preguntas']);
            $_SESSION['ia_borradores'][$borradorId]['generadas'] += count($resultado['preguntas']);
            registrarAuditoria($db, $docenteId, (int)$borrador['evaluacion_id'], 'ia_generar_mas', [
                'borrador' => $borradorId, 'modelo' => GEMINI_MODELO, 'pedidas' => $parametros['cantidad'],
                'generadas' => count($resultado['preguntas']), 'descartadas' => $resultado['descartadas'],
            ]);
            redirigirConMensaje($volver . '#pregunta-' . ($borrador['siguiente']), 'Se añadieron ' . count($resultado['preguntas']) . ' pregunta(s) nuevas'
                . ($resultado['descartadas'] > 0 ? ' (se descartaron ' . $resultado['descartadas'] . ' mal formadas)' : '') . '.');
        }

        if ($accion === 'guardar') {
            $aprobadas = array_values(array_filter($borrador['preguntas'], static fn(array $p): bool => $p['estado'] === 'aprobada'));
            if (!$aprobadas) {
                redirigirConMensaje($volver, 'Aprueba al menos una pregunta antes de guardar.', 'warning');
            }
            $alBanco = ($_POST['al_banco'] ?? '') === '1';
            $conteo = array_count_values(array_column($borrador['preguntas'], 'estado'));
            $db->beginTransaction();
            // Se bloquea la evaluación y se vuelve a comprobar que no tenga intentos.
            if (buscarEvaluacionPropia($db, (int)$borrador['evaluacion_id'], $docenteId, true) === null
                || evaluacionTieneIntentos($db, (int)$borrador['evaluacion_id'])) {
                $db->rollBack();
                redirigirConMensaje($volver, 'La evaluación ya tiene intentos: no se le pueden añadir preguntas.', 'danger');
            }
            guardarPreguntasIa($db, $docenteId, (int)$borrador['evaluacion_id'], $aprobadas, $alBanco);
            registrarAuditoria($db, $docenteId, (int)$borrador['evaluacion_id'], 'ia_guardar', [
                'borrador' => $borradorId, 'origen' => $borrador['origen'], 'generadas' => $borrador['generadas'],
                'aprobadas' => count($aprobadas), 'descartadas' => $conteo['descartada'] ?? 0,
                'sin_revisar' => $conteo['pendiente'] ?? 0, 'editadas' => count(array_filter($aprobadas, static fn(array $p): bool => !empty($p['editada']))),
                'al_banco' => $alBanco,
            ]);
            $db->commit();
            unset($_SESSION['ia_borradores'][$borradorId]);
            redirigirConMensaje('preguntas.php?evaluacion=' . (int)$borrador['evaluacion_id'],
                count($aprobadas) . ' pregunta(s) generadas con IA guardadas en la evaluación' . ($alBanco ? ' y en tu banco de preguntas' : '') . '.');
        }

        if ($accion === 'cancelar') {
            registrarAuditoria($db, $docenteId, (int)$borrador['evaluacion_id'], 'ia_cancelar', [
                'borrador' => $borradorId, 'generadas' => $borrador['generadas'], 'aprobadas' => 0,
            ]);
            unset($_SESSION['ia_borradores'][$borradorId]);
            redirigirConMensaje('ia.php?evaluacion=' . (int)$borrador['evaluacion_id'], 'Borrador descartado. No se guardó ninguna pregunta.', 'info');
        }

        if ($editando === null) {
            redirigirConMensaje($volver, 'Acción no válida.', 'danger');
        }
    } elseif (isset($_GET['editar'])) {
        $numero = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
        $editando = $numero !== false && isset($borrador['preguntas'][$numero]) ? $numero : null;
    }
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en la revisión de IA: ' . $exception);
    http_response_code(500);
    exit('No se pudo completar la acción. Inténtalo más tarde.');
}

$conteo = array_count_values(array_column($borrador['preguntas'], 'estado')) + ['pendiente' => 0, 'aprobada' => 0, 'descartada' => 0];
$mensaje = tomarMensaje();
$titulo = 'Revisar preguntas generadas';
$subtitulo = 'Destino: ' . $evaluacion['titulo'];
$seccion = 'ia';
$scripts = ['../assets/js/ia.js'];
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_ia_revision.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
