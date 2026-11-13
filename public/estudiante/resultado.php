<?php
declare(strict_types=1);

// Resultado de un intento calificado: nota y, si la evaluación lo permite, retroalimentación por pregunta.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];
$db = db();

$intentoId = leerId($_GET['intento'] ?? null);
if ($intentoId === null) {
    redirigirConMensaje('evaluaciones.php', 'El intento solicitado no es válido.', 'danger');
}
try {
    finalizarIntentosVencidos($db, $estudianteId);
    $intento = intentoDelEstudiante($db, $intentoId, $estudianteId);
    if ($intento === null) {
        redirigirConMensaje('evaluaciones.php', 'Ese intento no existe.', 'danger');
    }
    if ($intento['estado'] === 'en_curso') {
        header('Location: examen.php?intento=' . $intentoId, true, 303);
        exit;
    }
    $detalle = (int)$intento['mostrar_retroalimentacion'] === 1 ? detalleResultado($db, $intento) : [];
    $consulta = $db->prepare('SELECT COUNT(*) FROM intentos WHERE evaluacion_id = ? AND estudiante_id = ?');
    $consulta->execute([$intento['evaluacion_id'], $estudianteId]);
    $usados = (int)$consulta->fetchColumn();
} catch (Throwable $exception) {
    error_log('Error al mostrar el resultado: ' . $exception);
    http_response_code(500);
    exit('No se pudo mostrar el resultado. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Resultado';
$subtitulo = $intento['titulo'] . ' · intento ' . (int)$intento['numero'];
$seccion = 'evaluaciones';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_resultado.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
