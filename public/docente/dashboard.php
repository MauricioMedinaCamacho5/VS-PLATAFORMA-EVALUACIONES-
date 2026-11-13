<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'evaluaciones.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

try {
    // Tarjetas: todo se limita a las evaluaciones del docente que ha iniciado sesión.
    $consulta = $db->prepare(
        'SELECT COUNT(*) AS total, COALESCE(SUM(publicada = 1), 0) AS publicadas
         FROM evaluaciones WHERE docente_id = ?'
    );
    $consulta->execute([$docenteId]);
    $conteo = $consulta->fetch();

    $consulta = $db->prepare(
        'SELECT COUNT(*) FROM preguntas p JOIN evaluaciones e ON e.id = p.evaluacion_id WHERE e.docente_id = ?'
    );
    $consulta->execute([$docenteId]);
    $totalPreguntas = (int)$consulta->fetchColumn();

    // Intentos "recibidos" = ya enviados por el estudiante (no cuenta los que siguen en curso).
    $consulta = $db->prepare(
        'SELECT COUNT(*) FROM intentos i JOIN evaluaciones e ON e.id = i.evaluacion_id
         WHERE e.docente_id = ? AND i.enviado_en IS NOT NULL'
    );
    $consulta->execute([$docenteId]);
    $totalIntentos = (int)$consulta->fetchColumn();

    $consulta = $db->prepare(
        'SELECT id, titulo, publicada, cierre FROM evaluaciones
         WHERE docente_id = ? ORDER BY creado_en DESC, id DESC LIMIT 5'
    );
    $consulta->execute([$docenteId]);
    $ultimas = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al cargar el panel del docente: ' . $exception);
    http_response_code(500);
    exit('No se pudo cargar el panel. Inténtalo más tarde.');
}

$estadisticas = [
    ['Total de evaluaciones', (int)$conteo['total'], 'bi-clipboard-data', 'stat-azul'],
    ['Evaluaciones publicadas', (int)$conteo['publicadas'], 'bi-broadcast', 'stat-verde'],
    ['Total de preguntas', $totalPreguntas, 'bi-question-circle', 'stat-naranja'],
    ['Intentos recibidos', $totalIntentos, 'bi-people', 'stat-celeste'],
];
$mensaje = tomarMensaje();
$titulo = 'Panel del docente';
$subtitulo = 'Resumen de tus evaluaciones y accesos rápidos.';
$seccion = 'inicio';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_inicio.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
