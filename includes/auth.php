<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

function requerirRol(string $rol): array
{
    header('Cache-Control: no-store, private');
    try {
        $usuario = currentUser();
    } catch (Throwable $exception) {
        error_log('Error al verificar el acceso: ' . $exception);
        http_response_code(503);
        exit('No se pudo verificar el acceso. Inténtalo más tarde.');
    }
    if ($usuario === null || $usuario['rol'] !== $rol) {
        $_SESSION['mensaje_acceso'] = $usuario === null
            ? 'Inicia sesión con una cuenta autorizada para acceder.'
            : 'No tienes permiso para acceder a ese panel.';
        header('Location: ../index.html', true, 303);
        exit;
    }
    return $usuario;
}
