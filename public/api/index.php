<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

function iniciarSesion(int $id, int $status): never
{
    if (!session_regenerate_id(true)) {
        throw new RuntimeException('No se pudo regenerar la sesión.');
    }
    $_SESSION['id'] = $id;
    unset($_SESSION['csrf']);
    $user = currentUser();
    $panel = $user ? dashboardPath($user['rol']) : null;
    if ($panel === null) {
        destroySession();
        respond(403, ['error' => 'Tu cuenta no tiene un rol válido. Contacta al administrador.']);
    }
    respond($status, ['usuario' => $user, 'csrf' => csrfToken(), 'panel' => $panel]);
}

try {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && $action === 'session') {
        $message = $_SESSION['mensaje_acceso'] ?? null;
        unset($_SESSION['mensaje_acceso']);
        $user = currentUser();
        respond(200, [
            'csrf' => csrfToken(),
            'usuario' => $user,
            'panel' => $user ? dashboardPath($user['rol']) : null,
            'mensaje' => $message,
            'aviso_privacidad' => $config['privacy_notice'] ?? '',
        ]);
    }

    if ($method !== 'POST' || !in_array($action, ['login', 'register', 'logout'], true)) {
        respond(404, ['error' => 'Operación no encontrada.']);
    }
    requireCsrf();
    $raw = file_get_contents('php://input', false, null, 0, 16385);
    if ($raw === false || strlen($raw) > 16384) {
        respond(413, ['error' => 'La solicitud es demasiado grande.']);
    }
    $input = json_decode($raw, true);
    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        respond(400, ['error' => 'El cuerpo debe ser JSON válido.']);
    }

    if ($action === 'logout') {
        destroySession();
        respond(200, ['mensaje' => 'Sesión cerrada.']);
    }

    $email = $input['correo'] ?? null;
    $password = $input['password'] ?? null;
    if (!is_string($email) || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !is_string($password) || strlen($password) > 72) {
        respond(422, ['error' => 'Correo o contraseña inválidos.']);
    }
    $email = mb_strtolower(trim($email), 'UTF-8');

    if ($action === 'login') {
        $query = db()->prepare('SELECT id, password_hash, activo FROM usuarios WHERE correo = ?');
        $query->execute([$email]);
        $user = $query->fetch();
        $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify($password, $user === false ? $dummyHash : $user['password_hash']);
        if (!$valid || $user === false) {
            respond(401, ['error' => 'Correo o contraseña incorrectos.']);
        }
        if ((int)$user['activo'] !== 1) {
            respond(403, ['error' => 'Tu usuario está desactivado. Contacta al administrador.']);
        }
        iniciarSesion((int)$user['id'], 200);
    }

    $name = $input['nombre'] ?? null;
    $role = $input['rol'] ?? null;
    $notice = $config['privacy_notice'] ?? '';
    if (!is_string($notice) || trim($notice) === '') {
        respond(503, ['error' => 'El registro no está habilitado: falta configurar el aviso de privacidad.']);
    }
    if (!is_string($name) || trim($name) === ''
        || mb_strlen(trim($name), 'UTF-8') > 150
        || mb_strlen($password, 'UTF-8') < 8 || ($input['acepta_privacidad'] ?? false) !== true) {
        respond(422, ['error' => 'Indica nombre, contraseña de al menos 8 caracteres y acepta el aviso de privacidad.']);
    }
    if (!is_string($role) || !in_array($role, ['estudiante', 'docente'], true)) {
        respond(422, ['error' => 'Selecciona el rol estudiante o docente.']);
    }
    $query = db()->prepare(
        "INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo, privacidad_aceptada_en, aviso_privacidad)
         SELECT id, ?, ?, ?, 1, CURRENT_TIMESTAMP, ? FROM roles WHERE nombre = ?"
    );
    try {
        $query->execute([trim($name), $email, password_hash($password, PASSWORD_DEFAULT), $notice, $role]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000' && (int)($exception->errorInfo[1] ?? 0) === 1062) {
            respond(409, ['error' => 'El correo ya está registrado.']);
        }
        throw $exception;
    }
    if ($query->rowCount() !== 1) {
        throw new RuntimeException('Falta el rol solicitado en la base de datos.');
    }
    iniciarSesion((int)db()->lastInsertId(), 201);
} catch (Throwable $exception) {
    error_log('Error en API de evaluaciones: ' . $exception);
    respond(500, ['error' => 'No se pudo procesar la solicitud.']);
}
