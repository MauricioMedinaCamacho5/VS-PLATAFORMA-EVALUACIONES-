<?php
declare(strict_types=1);

// Preguntas de una evaluación del docente: añadir y eliminar.
// Tipos: selección múltiple (una correcta), verdadero/falso y respuesta corta (calificación automática exacta).

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'evaluaciones.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

const MAX_OPCIONES = 6;

$evaluacionId = leerId($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['evaluacion'] ?? null) : ($_GET['evaluacion'] ?? null));
if ($evaluacionId === null) {
    redirigirConMensaje('evaluaciones.php', 'La evaluación solicitada no es válida.', 'danger');
}
$volver = 'preguntas.php?evaluacion=' . $evaluacionId;

$errores = [];
$valores = ['tipo' => 'seleccion_multiple', 'enunciado' => '', 'puntaje' => '1', 'retroalimentacion' => '',
    'opciones' => array_fill(0, 4, ''), 'correcta' => '0', 'correcta_vf' => 'v', 'respuesta_corta' => ''];

try {
    $evaluacion = buscarEvaluacionPropia($db, $evaluacionId, $docenteId);
    if ($evaluacion === null) {
        redirigirConMensaje('evaluaciones.php', 'La evaluación no existe o no te pertenece.', 'danger');
    }
    $bloqueada = evaluacionTieneIntentos($db, $evaluacionId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCsrfFormulario();
        if ($bloqueada) {
            redirigirConMensaje($volver, 'Ya hay estudiantes que presentaron esta evaluación: sus preguntas no se pueden cambiar. Duplícala para hacer otra versión.', 'warning');
        }
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'eliminar') {
            $preguntaId = leerId($_POST['pregunta'] ?? null);
            $db->beginTransaction();
            $borrar = $db->prepare('DELETE FROM preguntas WHERE id = ? AND evaluacion_id = ?');
            $borrar->execute([$preguntaId ?? 0, $evaluacionId]);
            if ($borrar->rowCount() === 1) {
                registrarAuditoria($db, $docenteId, $evaluacionId, 'pregunta_eliminar', ['pregunta_id' => $preguntaId]);
            }
            $db->commit();
            redirigirConMensaje($volver, 'Pregunta eliminada.');
        }

        if ($accion === 'crear') {
            $texto = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
            $valores['tipo'] = $texto('tipo');
            $valores['enunciado'] = $texto('enunciado');
            $valores['puntaje'] = $texto('puntaje');
            $valores['retroalimentacion'] = $texto('retroalimentacion');
            $valores['correcta'] = $texto('correcta');
            $valores['correcta_vf'] = $texto('correcta_vf');
            $valores['respuesta_corta'] = $texto('respuesta_corta');
            $opcionesRecibidas = is_array($_POST['opciones'] ?? null) ? array_slice($_POST['opciones'], 0, MAX_OPCIONES) : [];
            $valores['opciones'] = array_pad(array_map(static fn($o): string => is_string($o) ? trim($o) : '', $opcionesRecibidas), 4, '');

            foreach (['enunciado', 'retroalimentacion', 'respuesta_corta'] as $campo) {
                if (!mb_check_encoding($valores[$campo], 'UTF-8')) {
                    $errores[$campo] = 'El texto contiene caracteres no válidos.';
                    $valores[$campo] = mb_scrub($valores[$campo], 'UTF-8');
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

            $opciones = [];      // [texto, correcta]
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
                    $opciones[] = [$textoOpcion, $indice === $correcta ? 1 : 0];
                }
                if (count($opciones) < 2) {
                    $errores['opciones'] = 'Escribe al menos 2 opciones.';
                } elseif (array_sum(array_column($opciones, 1)) !== 1) {
                    $errores['opciones'] = 'Marca cuál de las opciones escritas es la correcta.';
                }
            } elseif ($valores['tipo'] === 'verdadero_falso') {
                if (!in_array($valores['correcta_vf'], ['v', 'f'], true)) {
                    $errores['correcta_vf'] = 'Indica si la afirmación es verdadera o falsa.';
                }
                $opciones = [['Verdadero', $valores['correcta_vf'] === 'v' ? 1 : 0], ['Falso', $valores['correcta_vf'] === 'f' ? 1 : 0]];
            } else {
                if ($valores['respuesta_corta'] === '') {
                    $errores['respuesta_corta'] = 'Escribe la respuesta esperada.';
                } elseif (mb_strlen($valores['respuesta_corta'], 'UTF-8') > 500) {
                    $errores['respuesta_corta'] = 'La respuesta esperada admite como máximo 500 caracteres.';
                }
                $respuestaCorta = $valores['respuesta_corta'];
            }

            if (!$errores) {
                $db->beginTransaction();
                $posicion = $db->prepare('SELECT COALESCE(MAX(posicion), 0) + 1 FROM preguntas WHERE evaluacion_id = ?');
                $posicion->execute([$evaluacionId]);
                $db->prepare(
                    'INSERT INTO preguntas (evaluacion_id, tipo, enunciado, respuesta_corta, puntaje, retroalimentacion, posicion)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $evaluacionId, $valores['tipo'], $valores['enunciado'], $respuestaCorta,
                    number_format(round($puntaje, 2), 2, '.', ''),
                    $valores['retroalimentacion'] === '' ? null : $valores['retroalimentacion'],
                    (int)$posicion->fetchColumn(),
                ]);
                $preguntaId = (int)$db->lastInsertId();
                $insertarOpcion = $db->prepare('INSERT INTO opciones (pregunta_id, texto, correcta, posicion) VALUES (?, ?, ?, ?)');
                foreach ($opciones as $indice => [$textoOpcion, $esCorrecta]) {
                    $insertarOpcion->execute([$preguntaId, $textoOpcion, $esCorrecta, $indice + 1]);
                }
                registrarAuditoria($db, $docenteId, $evaluacionId, 'pregunta_crear', ['pregunta_id' => $preguntaId, 'tipo' => $valores['tipo']]);
                $db->commit();
                redirigirConMensaje($volver . '#nueva', 'Pregunta añadida.');
            }
            http_response_code(422);
        }
    }

    // El docente sí ve las respuestas correctas de sus propias preguntas.
    $consulta = $db->prepare('SELECT * FROM preguntas WHERE evaluacion_id = ? ORDER BY posicion, id');
    $consulta->execute([$evaluacionId]);
    $preguntas = $consulta->fetchAll();
    $consulta = $db->prepare('SELECT o.* FROM opciones o JOIN preguntas p ON p.id = o.pregunta_id WHERE p.evaluacion_id = ? ORDER BY o.posicion, o.id');
    $consulta->execute([$evaluacionId]);
    $opcionesPorPregunta = [];
    foreach ($consulta->fetchAll() as $opcion) {
        $opcionesPorPregunta[(int)$opcion['pregunta_id']][] = $opcion;
    }
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en preguntas: ' . $exception);
    http_response_code(500);
    exit('No se pudieron gestionar las preguntas. Inténtalo más tarde.');
}

$puntajeTotal = array_sum(array_map(static fn(array $p): float => (float)$p['puntaje'], $preguntas));
$mensaje = tomarMensaje();
$titulo = 'Preguntas';
$subtitulo = $evaluacion['titulo'];
$seccion = 'evaluaciones';
$scripts = ['../assets/js/preguntas.js'];
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_preguntas.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
