<?php
declare(strict_types=1);

$configPath = getenv('EVALUACIONES_CONFIG');
if ($configPath === false || $configPath === '') {
    $configPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'evaluaciones-config' . DIRECTORY_SEPARATOR . 'config.php';
}
if (!is_file($configPath)) {
    error_log('Falta el archivo de configuración de evaluaciones.');
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => 'La aplicación no está configurada.']));
}
$config = require $configPath;
if (!is_array($config) || !isset($config['db'])) {
    error_log('Configuración de evaluaciones inválida.');
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => 'La aplicación no está configurada correctamente.']));
}

// Configuración de la IA (Gemini). Es opcional: sin ella el resto de la plataforma funciona
// y el módulo de IA muestra un aviso. config/ queda fuera de public/ y no es accesible por web.
$configIa = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
if (is_file($configIa)) {
    require_once $configIa;
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

function db(): PDO
{
    global $config;
    static $pdo = null;
    if ($pdo === null) {
        $settings = $config['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $settings['host'],
            $settings['port'],
            $settings['name']
        );
        $pdo = new PDO($dsn, $settings['user'], $settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function respond(int $status, array $data): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    exit(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function requireCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        respond(403, ['error' => 'La sesión del formulario ha vencido. Recarga la página.']);
    }
}

function dashboardPath(string $role): ?string
{
    return match ($role) {
        'administrador' => 'admin/dashboard.php',
        'docente' => 'docente/dashboard.php',
        'estudiante' => 'estudiante/dashboard.php',
        default => null,
    };
}

function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }
    session_destroy();
}

function currentUser(): ?array
{
    if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
        return null;
    }
    $query = db()->prepare(
        'SELECT u.id, u.nombre, u.correo, r.nombre AS rol
         FROM usuarios u JOIN roles r ON r.id = u.rol_id
         WHERE u.id = ? AND u.activo = 1'
    );
    $query->execute([$_SESSION['id'] ?? $_SESSION['user_id']]);
    $user = $query->fetch();
    if ($user === false) {
        unset($_SESSION['id'], $_SESSION['user_id'], $_SESSION['nombre'], $_SESSION['correo'], $_SESSION['rol']);
        return null;
    }
    $_SESSION['id'] = (int)$user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['correo'] = $user['correo'];
    $_SESSION['rol'] = $user['rol'];
    unset($_SESSION['user_id']);
    return $user;
}
