<?php
declare(strict_types=1);

// "Mis clases" del estudiante: tarjetas de colores y botón para unirse con código.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];

try {
    $consulta = db()->prepare(
        "SELECT c.id, c.nombre, c.color, i.nombre AS institucion, u.nombre AS docente,
                (SELECT COUNT(*) FROM evaluacion_clases ec JOIN evaluaciones e ON e.id = ec.evaluacion_id
                  WHERE ec.clase_id = c.id AND e.publicada = 1 AND NOW() BETWEEN e.apertura AND e.cierre) AS disponibles
         FROM clase_estudiantes ce
         JOIN clases c ON c.id = ce.clase_id
         JOIN usuarios u ON u.id = c.docente_id
         LEFT JOIN instituciones i ON i.id = c.institucion_id
         WHERE ce.estudiante_id = ? ORDER BY ce.unido_en DESC"
    );
    $consulta->execute([$estudianteId]);
    $clases = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al listar las clases del estudiante: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar tus clases. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Mis clases';
$subtitulo = 'Entra a una clase para ver sus evaluaciones.';
$seccion = 'clases';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_clases.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
