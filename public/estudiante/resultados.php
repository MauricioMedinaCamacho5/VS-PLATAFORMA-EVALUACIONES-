<?php
declare(strict_types=1);

// Historial de evaluaciones calificadas del estudiante.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];
$db = db();

try {
    finalizarIntentosVencidos($db, $estudianteId);
    $resultados = resultadosDelEstudiante($db, $estudianteId);
} catch (Throwable $exception) {
    error_log('Error al listar resultados del estudiante: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar tus resultados. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Mis resultados';
$subtitulo = 'Consulta las calificaciones de tus evaluaciones completadas.';
$seccion = 'resultados';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_resultados.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
