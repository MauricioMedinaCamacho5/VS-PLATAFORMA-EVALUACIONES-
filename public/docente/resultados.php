<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'evaluaciones.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();
$evaluacionRecibida = $_GET['evaluacion'] ?? '';
$evaluacionId = $evaluacionRecibida === '' ? null : leerId($evaluacionRecibida);
$intentoId = isset($_GET['intento']) ? leerId($_GET['intento']) : null;
$pagina = isset($_GET['pagina']) ? leerId($_GET['pagina']) : 1;
if (($evaluacionRecibida !== '' && $evaluacionId === null)
    || (isset($_GET['intento']) && $intentoId === null)
    || $pagina === null) {
    redirigirConMensaje('resultados.php', 'El filtro solicitado no es válido.', 'danger');
}

try {
    $consulta = $db->prepare('SELECT id, titulo FROM evaluaciones WHERE docente_id = ? ORDER BY creado_en DESC, id DESC');
    $consulta->execute([$docenteId]);
    $evaluaciones = $consulta->fetchAll();
    if ($evaluacionId !== null && !in_array($evaluacionId, array_map('intval', array_column($evaluaciones, 'id')), true)) {
        redirigirConMensaje('resultados.php', 'La evaluación no existe o no te pertenece.', 'danger');
    }

    $filtro = $evaluacionId === null ? '' : ' AND e.id = :evaluacion';
    $consulta = $db->prepare(
        "SELECT COUNT(*) AS total, AVG(i.nota) AS promedio
         FROM intentos i JOIN evaluaciones e ON e.id = i.evaluacion_id
         WHERE e.docente_id = :docente AND i.estado = 'calificado' AND i.nota IS NOT NULL{$filtro}"
    );
    $consulta->bindValue(':docente', $docenteId, PDO::PARAM_INT);
    if ($evaluacionId !== null) {
        $consulta->bindValue(':evaluacion', $evaluacionId, PDO::PARAM_INT);
    }
    $consulta->execute();
    $resumen = $consulta->fetch();
    $totalResultados = (int)$resumen['total'];
    $porPagina = 100;
    $totalPaginas = max(1, (int)ceil($totalResultados / $porPagina));
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
    }

    $consulta = $db->prepare(
        "SELECT i.id, i.numero, i.enviado_en, i.nota, e.id AS evaluacion_id, e.titulo,
                e.nota_minima, u.nombre AS estudiante, u.correo
         FROM intentos i
         JOIN evaluaciones e ON e.id = i.evaluacion_id
         JOIN usuarios u ON u.id = i.estudiante_id
         WHERE e.docente_id = :docente AND i.estado = 'calificado' AND i.nota IS NOT NULL{$filtro}
         ORDER BY i.enviado_en DESC, i.id DESC
         LIMIT :limite OFFSET :desplazamiento"
    );
    $consulta->bindValue(':docente', $docenteId, PDO::PARAM_INT);
    if ($evaluacionId !== null) {
        $consulta->bindValue(':evaluacion', $evaluacionId, PDO::PARAM_INT);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':desplazamiento', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
    $consulta->execute();
    $resultados = $consulta->fetchAll();

    $detalleIntento = null;
    $detallePreguntas = [];
    if ($intentoId !== null) {
        $consulta = $db->prepare(
            "SELECT i.id, i.numero, i.enviado_en, i.nota, e.id AS evaluacion_id, e.titulo, e.nota_minima,
                    u.nombre AS estudiante, u.correo
             FROM intentos i
             JOIN evaluaciones e ON e.id = i.evaluacion_id
             JOIN usuarios u ON u.id = i.estudiante_id
             WHERE i.id = ? AND e.docente_id = ? AND i.estado = 'calificado'
                 AND i.nota IS NOT NULL" . ($evaluacionId === null ? '' : ' AND e.id = ?')
        );
        $parametros = [$intentoId, $docenteId];
        if ($evaluacionId !== null) {
            $parametros[] = $evaluacionId;
        }
        $consulta->execute($parametros);
        $detalleIntento = $consulta->fetch();
        if ($detalleIntento === false) {
            redirigirConMensaje('resultados.php', 'Ese resultado no existe o no te pertenece.', 'danger');
        }
        $consulta = $db->prepare(
            'SELECT p.enunciado, p.tipo, p.puntaje, p.respuesta_corta,
                    r.texto AS respuesta_texto, respuesta.texto AS respuesta_opcion,
                    correcta.texto AS opcion_correcta, r.puntaje_obtenido
             FROM preguntas p
             LEFT JOIN respuestas r ON r.pregunta_id = p.id AND r.intento_id = ?
             LEFT JOIN opciones respuesta ON respuesta.id = r.opcion_id AND respuesta.pregunta_id = p.id
             LEFT JOIN opciones correcta ON correcta.pregunta_id = p.id AND correcta.correcta = 1
             WHERE p.evaluacion_id = ? ORDER BY p.posicion, p.id'
        );
        $consulta->execute([$intentoId, $detalleIntento['evaluacion_id']]);
        $detallePreguntas = $consulta->fetchAll();
    }
} catch (Throwable $exception) {
    error_log('Error al consultar resultados del docente: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar los resultados. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Resultados';
$subtitulo = 'Consulta las calificaciones y respuestas de tus estudiantes.';
$seccion = 'resultados';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_resultados.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
