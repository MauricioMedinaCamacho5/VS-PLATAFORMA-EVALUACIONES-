<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';

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
    // Si llegó por un enlace /unirse.php?codigo=... sin sesión, ahora se une a la clase automáticamente.
    $panel = procesarUnionPendiente(db(), $user) ?? $panel;
    respond($status, ['usuario' => $user, 'csrf' => csrfToken(), 'panel' => $panel]);
}

try {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && $action === 'session') {
        $message = $_SESSION['mensaje_acceso'] ?? null;
        unset($_SESSION['mensaje_acceso']);
        $user = currentUser();
        $codigoPendiente = codigoUnionPendiente();
        $clasePendiente = $codigoPendiente !== null ? claseActivaPorCodigo(db(), $codigoPendiente) : null;
        respond(200, [
            'union_pendiente' => $clasePendiente === null ? null : [
                'codigo' => $codigoPendiente, 'clase' => $clasePendiente['nombre'], 'docente' => $clasePendiente['docente'],
            ],
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
    $emailValido = is_string($email) && strlen(trim($email)) <= 254 && filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    $email = $emailValido ? mb_strtolower(trim($email), 'UTF-8') : '';

    if ($action === 'login') {
        if (!$emailValido || !is_string($password) || strlen($password) > 72) {
            respond(422, ['error' => 'Correo o contraseña inválidos.']);
        }
        // "publico" = página principal (estudiantes y docentes); "admin" = acceso de administración.
        $portal = $input['portal'] ?? 'publico';
        if (!in_array($portal, ['publico', 'admin'], true)) {
            respond(422, ['error' => 'Acceso no válido.']);
        }
        $query = db()->prepare(
            'SELECT u.id, u.password_hash, u.activo, r.nombre AS rol
             FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.correo = ?'
        );
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
        $esAdmin = $user['rol'] === 'administrador';
        if ($portal === 'admin' && !$esAdmin) {
            respond(403, ['error' => 'Esta cuenta no tiene acceso de administración. Ingresa por la página principal.']);
        }
        if ($portal === 'publico' && $esAdmin) {
            respond(403, ['error' => 'Las cuentas de administrador ingresan por el acceso de administración.']);
        }
        iniciarSesion((int)$user['id'], 200);
    }

    $name = $input['nombre'] ?? null;
    $role = $input['rol'] ?? null;
    $notice = $config['privacy_notice'] ?? '';
    if (!is_string($notice) || trim($notice) === '') {
        respond(503, ['error' => 'El registro no está habilitado: falta configurar el aviso de privacidad.']);
    }
    // Validación del registro campo por campo: la interfaz muestra cada mensaje bajo su campo.
    $errores = [];
    if (!is_string($name) || !mb_check_encoding($name, 'UTF-8') || trim($name) === '') {
        $errores['nombre'] = 'Escribe tu nombre completo.';
    } elseif (mb_strlen(trim($name), 'UTF-8') > 150) {
        $errores['nombre'] = 'El nombre admite como máximo 150 caracteres.';
    }
    if (!is_string($input['correo'] ?? null) || trim($input['correo']) === '') {
        $errores['correo'] = 'Escribe tu correo electrónico.';
    } elseif (!$emailValido) {
        $errores['correo'] = 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
    }
    if (!is_string($role) || !in_array($role, ['estudiante', 'docente'], true)) {
        $errores['rol'] = 'Selecciona si eres estudiante o docente.';
    }
    if (!is_string($password) || $password === '') {
        $errores['password'] = 'Escribe una contraseña.';
    } elseif (!mb_check_encoding($password, 'UTF-8') || mb_strlen($password, 'UTF-8') < 8) {
        $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (strlen($password) > 72) {
        $errores['password'] = 'La contraseña es demasiado larga (máximo 72 bytes).';
    }
    if (($input['acepta_privacidad'] ?? false) !== true) {
        $errores['acepta_privacidad'] = 'Debes aceptar el aviso de tratamiento de datos.';
    }
    if ($errores) {
        respond(422, ['error' => 'Revisa los campos marcados.', 'errores' => $errores]);
    }
    $query = db()->prepare(
        "INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo, privacidad_aceptada_en, aviso_privacidad)
         SELECT id, ?, ?, ?, 1, CURRENT_TIMESTAMP, ? FROM roles WHERE nombre = ?"
    );
    try {
        $query->execute([trim($name), $email, password_hash($password, PASSWORD_DEFAULT), $notice, $role]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000' && (int)($exception->errorInfo[1] ?? 0) === 1062) {
            respond(409, ['error' => 'El correo ya está registrado.', 'errores' => ['correo' => 'Este correo ya está registrado. Inicia sesión o usa otro.']]);
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
