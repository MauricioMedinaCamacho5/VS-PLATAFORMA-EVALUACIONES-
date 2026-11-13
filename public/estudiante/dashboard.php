<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];
$db = db();

try {
    finalizarIntentosVencidos($db, $estudianteId);
    $evaluaciones = evaluacionesDelEstudiante($db, $estudianteId);
    $consulta = $db->prepare('SELECT COUNT(*) FROM clase_estudiantes WHERE estudiante_id = ?');
    $consulta->execute([$estudianteId]);
    $totalClases = (int)$consulta->fetchColumn();
} catch (Throwable $exception) {
    error_log('Error al cargar el panel del estudiante: ' . $exception);
    http_response_code(500);
    exit('No se pudo cargar el panel. Inténtalo más tarde.');
}

$conteo = ['abierta' => 0, 'proxima' => 0, 'cerrada' => 0];
foreach ($evaluaciones as $evaluacion) {
    $conteo[$evaluacion['estado']]++;
}
$estadisticas = [
    ['Mis clases', $totalClases, 'bi-easel', 'stat-azul'],
    ['Disponibles ahora', $conteo['abierta'], 'bi-play-circle', 'stat-verde'],
    ['Próximamente', $conteo['proxima'], 'bi-hourglass-split', 'stat-naranja'],
];
$disponibles = array_values(array_filter($evaluaciones, static fn(array $e): bool => $e['estado'] === 'abierta'));

$mensaje = tomarMensaje();
$titulo = 'Panel del estudiante';
$subtitulo = 'Tus clases y evaluaciones de un vistazo.';
$seccion = 'inicio';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_inicio.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
