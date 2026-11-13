<?php
declare(strict_types=1);

// "Mis clases" del docente: tarjetas de colores con su código.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
$usuario = requerirRol('docente');

try {
    $consulta = db()->prepare(
        'SELECT c.id, c.nombre, c.color, c.codigo_union, c.codigo_activo, i.nombre AS institucion,
                (SELECT COUNT(*) FROM clase_estudiantes ce WHERE ce.clase_id = c.id) AS estudiantes,
                (SELECT COUNT(*) FROM evaluacion_clases ec WHERE ec.clase_id = c.id) AS evaluaciones
         FROM clases c LEFT JOIN instituciones i ON i.id = c.institucion_id
         WHERE c.docente_id = ? ORDER BY c.creado_en DESC, c.id DESC'
    );
    $consulta->execute([(int)$usuario['id']]);
    $clases = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al listar las clases del docente: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar tus clases. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Mis clases';
$subtitulo = 'Comparte el código o el enlace para que tus estudiantes se unan.';
$seccion = 'clases';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_clases.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
