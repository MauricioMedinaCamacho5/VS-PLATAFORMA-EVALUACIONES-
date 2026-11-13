<?php
declare(strict_types=1);

// Detalle de una clase del docente: código y enlace, estudiantes y evaluaciones asignadas.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrfFormulario();
    $claseId = leerId($_POST['id'] ?? null);
    $accion = $_POST['accion'] ?? '';
    if ($claseId === null || !in_array($accion, ['regenerar', 'activar', 'desactivar', 'sacar'], true)) {
        redirigirConMensaje('clases.php', 'Acción no válida.', 'danger');
    }
    $volver = 'clase.php?id=' . $claseId;
    try {
        $db->beginTransaction();
        $clase = buscarClasePropia($db, $claseId, $docenteId, true);
        if ($clase === null) {
            $db->rollBack();
            redirigirConMensaje('clases.php', 'La clase no existe o no te pertenece.', 'danger');
        }
        if ($accion === 'regenerar') {
            $codigo = asignarCodigoNuevo($db, $claseId);
            registrarAuditoria($db, $docenteId, null, 'clase_codigo_regenerar', ['clase_id' => $claseId, 'codigo' => $codigo]);
            $db->commit();
            redirigirConMensaje($volver, 'Nuevo código: ' . $codigo . '. El anterior ya no funciona.');
        }
        if ($accion === 'activar' || $accion === 'desactivar') {
            if ($accion === 'activar' && $clase['codigo_union'] === null) {
                $codigo = asignarCodigoNuevo($db, $claseId);
            } else {
                $db->prepare('UPDATE clases SET codigo_activo = ? WHERE id = ?')->execute([$accion === 'activar' ? 1 : 0, $claseId]);
            }
            registrarAuditoria($db, $docenteId, null, 'clase_codigo_' . $accion, ['clase_id' => $claseId]);
            $db->commit();
            redirigirConMensaje($volver, $accion === 'activar'
                ? 'Código activado: los estudiantes ya pueden unirse.'
                : 'Código desactivado: nadie más podrá unirse hasta que lo actives.', $accion === 'activar' ? 'success' : 'info');
        }
        // sacar
        $estudianteId = leerId($_POST['estudiante_id'] ?? null);
        $borrar = $db->prepare('DELETE FROM clase_estudiantes WHERE clase_id = ? AND estudiante_id = ?');
        $borrar->execute([$claseId, $estudianteId ?? 0]);
        if ($borrar->rowCount() === 0) {
            $db->rollBack();
            redirigirConMensaje($volver, 'Ese estudiante no está en la clase.', 'warning');
        }
        registrarAuditoria($db, $docenteId, null, 'clase_sacar_estudiante', ['clase_id' => $claseId, 'estudiante_id' => $estudianteId]);
        $db->commit();
        redirigirConMensaje($volver, 'Estudiante retirado de la clase. Sus intentos anteriores se conservan.');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Error en acción de clase: ' . $exception);
        http_response_code(500);
        exit('No se pudo completar la acción. Inténtalo más tarde.');
    }
}

$claseId = leerId($_GET['id'] ?? null);
if ($claseId === null) {
    redirigirConMensaje('clases.php', 'La clase solicitada no es válida.', 'danger');
}
try {
    $clase = buscarClasePropia($db, $claseId, $docenteId);
    if ($clase === null) {
        redirigirConMensaje('clases.php', 'La clase no existe o no te pertenece.', 'danger');
    }
    $consulta = $db->prepare(
        'SELECT u.id, u.nombre, u.correo, u.activo, ce.unido_en FROM clase_estudiantes ce
         JOIN usuarios u ON u.id = ce.estudiante_id WHERE ce.clase_id = ? ORDER BY u.nombre'
    );
    $consulta->execute([$claseId]);
    $estudiantes = $consulta->fetchAll();
    $consulta = $db->prepare(
        'SELECT e.id, e.titulo, e.publicada, e.cierre FROM evaluacion_clases ec
         JOIN evaluaciones e ON e.id = ec.evaluacion_id WHERE ec.clase_id = ? AND e.docente_id = ?
         ORDER BY e.cierre DESC'
    );
    $consulta->execute([$claseId, $docenteId]);
    $evaluaciones = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al abrir la clase: ' . $exception);
    http_response_code(500);
    exit('No se pudo abrir la clase. Inténtalo más tarde.');
}

$codigoVisible = $clase['codigo_union'] !== null && (int)$clase['codigo_activo'] === 1;
$enlaceUnion = $clase['codigo_union'] !== null ? enlaceUnion($clase['codigo_union']) : '';
$mensaje = tomarMensaje();
$titulo = $clase['nombre'];
$seccion = 'clases';
$scripts = ['../assets/js/clase.js'];
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_clase.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
