<?php
declare(strict_types=1);

// "Mis evaluaciones" del estudiante: publicadas en las clases a las que se unió.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];

try {
    finalizarIntentosVencidos(db(), $estudianteId);
    $evaluaciones = evaluacionesDelEstudiante(db(), $estudianteId);
} catch (Throwable $exception) {
    error_log('Error al listar evaluaciones del estudiante: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar tus evaluaciones. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Mis evaluaciones';
$subtitulo = 'Todas las evaluaciones de tus clases.';
$seccion = 'evaluaciones';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_evaluaciones.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
