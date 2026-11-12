<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Usa el botón de cerrar sesión.');
}
$token = $_POST['csrf'] ?? '';
if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
    http_response_code(403);
    exit('La sesión del formulario ha vencido. Recarga la página.');
}
destroySession();
header('Location: index.html?salida=1', true, 303);
exit;
