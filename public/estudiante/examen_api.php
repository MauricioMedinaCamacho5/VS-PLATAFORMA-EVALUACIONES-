<?php
declare(strict_types=1);

// Autoguardado del examen (JSON). POST {intento, respuestas: {pregunta_id: valor}} con cabecera X-CSRF-Token.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';

$usuario = currentUser();
if ($usuario === null || $usuario['rol'] !== 'estudiante') {
    respond(401, ['error' => 'Tu sesión terminó. Vuelve a iniciar sesión.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Método no permitido.']);
}
requireCsrf();
$raw = file_get_contents('php://input', false, null, 0, 262145);
if ($raw === false || strlen($raw) > 262144) {
    respond(413, ['error' => 'La solicitud es demasiado grande.']);
}
$entrada = json_decode($raw, true);
$intentoId = is_array($entrada) ? leerId($entrada['intento'] ?? null) : null;
if ($intentoId === null) {
    respond(400, ['error' => 'Solicitud no válida.']);
}

$db = db();
try {
    $db->beginTransaction();
    $intento = intentoDelEstudiante($db, $intentoId, (int)$usuario['id'], true);
    if ($intento === null) {
        $db->rollBack();
        respond(404, ['error' => 'El intento no existe.']);
    }
    if ($intento['estado'] !== 'en_curso') {
        $db->rollBack();
        respond(409, ['error' => 'Este intento ya se envió.', 'enviado' => true]);
    }
    $restantes = (int)$intento['segundos_restantes'];
    if ($restantes < -GRACIA_SEGUNDOS) {
        $db->rollBack();
        respond(409, ['error' => 'El tiempo se agotó.', 'vencido' => true, 'segundos_restantes' => 0]);
    }
    $guardadas = guardarRespuestas($db, $intento, $entrada['respuestas'] ?? []);
    $db->commit();
    respond(200, ['ok' => true, 'guardadas' => $guardadas, 'segundos_restantes' => max(0, $restantes)]);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en el autoguardado: ' . $exception);
    respond(500, ['error' => 'No se pudo guardar.']);
}
