<?php
declare(strict_types=1);

// "Presentar": GET muestra las instrucciones; POST inicia (o continúa) el intento y abre el examen.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrfFormulario();
    $id = leerId($_POST['id'] ?? null);
    if ($id === null) {
        redirigirConMensaje('evaluaciones.php', 'La evaluación solicitada no es válida.', 'danger');
    }
    try {
        [$intentoId, $error] = iniciarOContinuarIntento($db, $estudianteId, $id);
    } catch (Throwable $exception) {
        error_log('Error al iniciar el intento: ' . $exception);
        http_response_code(500);
        exit('No se pudo iniciar la evaluación. Inténtalo más tarde.');
    }
    if ($intentoId === null) {
        redirigirConMensaje('presentar.php?id=' . $id, (string)$error, 'warning');
    }
    header('Location: examen.php?intento=' . $intentoId, true, 303);
    exit;
}

$id = leerId($_GET['id'] ?? null);
if ($id === null) {
    redirigirConMensaje('evaluaciones.php', 'La evaluación solicitada no es válida.', 'danger');
}
try {
    finalizarIntentosVencidos($db, $estudianteId);
    // La misma consulta del listado garantiza que solo vea evaluaciones publicadas de sus clases.
    $evaluacion = evaluacionesDelEstudiante($db, $estudianteId, $id)[0] ?? null;
} catch (Throwable $exception) {
    error_log('Error al abrir la evaluación del estudiante: ' . $exception);
    http_response_code(500);
    exit('No se pudo abrir la evaluación. Inténtalo más tarde.');
}
if ($evaluacion === null) {
    redirigirConMensaje('evaluaciones.php', 'Esa evaluación no existe o no está asignada a tus clases.', 'danger');
}

$mensaje = tomarMensaje();
$titulo = $evaluacion['titulo'];
$subtitulo = 'Docente: ' . $evaluacion['docente'] . ($evaluacion['clases'] ? ' · ' . $evaluacion['clases'] : '');
$seccion = 'evaluaciones';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_presentar.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
