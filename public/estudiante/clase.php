<?php
declare(strict_types=1);

// Una clase del estudiante: datos de la clase y sus evaluaciones.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];
$db = db();

$claseId = leerId($_GET['id'] ?? null);
if ($claseId === null) {
    redirigirConMensaje('clases.php', 'La clase solicitada no es válida.', 'danger');
}
try {
    $clase = buscarClaseDelEstudiante($db, $claseId, $estudianteId);
    if ($clase === null) {
        redirigirConMensaje('clases.php', 'No formas parte de esa clase.', 'danger');
    }
    finalizarIntentosVencidos($db, $estudianteId);
    $evaluaciones = evaluacionesDelEstudiante($db, $estudianteId, null, $claseId);
} catch (Throwable $exception) {
    error_log('Error al abrir la clase del estudiante: ' . $exception);
    http_response_code(500);
    exit('No se pudo abrir la clase. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = $clase['nombre'];
$seccion = 'clases';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_clase.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
