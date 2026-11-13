<?php
declare(strict_types=1);

// Administrador: todas las clases, instituciones y docentes (solo consulta).

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
$usuario = requerirRol('administrador');
$db = db();

$filtro = $_GET['institucion'] ?? '';
$filtroId = $filtro === 'ninguna' ? 'ninguna' : leerId($filtro);

try {
    $totales = $db->query(
        "SELECT (SELECT COUNT(*) FROM clases) AS clases,
                (SELECT COUNT(*) FROM instituciones) AS instituciones,
                (SELECT COUNT(DISTINCT docente_id) FROM clases) AS docentes,
                (SELECT COUNT(DISTINCT estudiante_id) FROM clase_estudiantes) AS estudiantes"
    )->fetch();

    $instituciones = $db->query(
        'SELECT i.id, i.nombre, COUNT(c.id) AS clases, COUNT(DISTINCT c.docente_id) AS docentes
         FROM instituciones i LEFT JOIN clases c ON c.institucion_id = i.id
         GROUP BY i.id, i.nombre ORDER BY i.nombre'
    )->fetchAll();

    $docentes = $db->query(
        "SELECT u.id, u.nombre, u.correo, u.activo, COUNT(c.id) AS clases,
                (SELECT COUNT(*) FROM evaluaciones e WHERE e.docente_id = u.id) AS evaluaciones
         FROM usuarios u JOIN roles r ON r.id = u.rol_id
         LEFT JOIN clases c ON c.docente_id = u.id
         WHERE r.nombre = 'docente'
         GROUP BY u.id, u.nombre, u.correo, u.activo ORDER BY u.nombre"
    )->fetchAll();

    $sql = 'SELECT c.id, c.nombre, c.color, c.codigo_union, c.codigo_activo, c.creado_en,
                   i.nombre AS institucion, u.nombre AS docente,
                   (SELECT COUNT(*) FROM clase_estudiantes ce WHERE ce.clase_id = c.id) AS estudiantes,
                   (SELECT COUNT(*) FROM evaluacion_clases ec WHERE ec.clase_id = c.id) AS evaluaciones
            FROM clases c JOIN usuarios u ON u.id = c.docente_id
            LEFT JOIN instituciones i ON i.id = c.institucion_id';
    if ($filtroId === 'ninguna') {
        $consulta = $db->prepare($sql . ' WHERE c.institucion_id IS NULL ORDER BY c.nombre');
        $consulta->execute();
    } elseif ($filtroId !== null) {
        $consulta = $db->prepare($sql . ' WHERE c.institucion_id = ? ORDER BY c.nombre');
        $consulta->execute([$filtroId]);
    } else {
        $consulta = $db->prepare($sql . ' ORDER BY i.nombre IS NULL, i.nombre, c.nombre');
        $consulta->execute();
    }
    $clases = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al consultar clases (administrador): ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar las clases. Inténtalo más tarde.');
}

$estadisticas = [
    ['Clases', (int)$totales['clases'], 'bi-easel', 'stat-azul'],
    ['Instituciones', (int)$totales['instituciones'], 'bi-building', 'stat-verde'],
    ['Docentes con clases', (int)$totales['docentes'], 'bi-person-badge', 'stat-naranja'],
    ['Estudiantes inscritos', (int)$totales['estudiantes'], 'bi-people', 'stat-celeste'],
];
$titulo = 'Clases';
$subtitulo = 'Todas las clases de la plataforma, por institución y docente.';
$seccion = 'clases';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'admin_clases.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
