<?php
declare(strict_types=1);

// Enlace para compartir: /unirse.php?codigo=AB12CD
//  - Sin sesión: guarda el código y manda al login/registro; al entrar se une automáticamente.
//  - Estudiante con sesión: va a la página de confirmación de su panel.
//  - Docente o administrador: vuelve a su panel con un aviso.

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
header('Cache-Control: no-store, private');

$codigo = normalizarCodigo($_GET['codigo'] ?? null);
try {
    $usuario = currentUser();
} catch (Throwable $exception) {
    error_log('Error al verificar la sesión en unirse.php: ' . $exception);
    http_response_code(503);
    exit('No se pudo verificar la sesión. Inténtalo más tarde.');
}

if ($usuario === null) {
    if ($codigo !== null) {
        guardarUnionPendiente($codigo);
    }
    header('Location: index.html?unirse=1', true, 303);
    exit;
}
if ($usuario['rol'] === 'estudiante') {
    header('Location: estudiante/unirse.php' . ($codigo !== null ? '?codigo=' . rawurlencode($codigo) : ''), true, 303);
    exit;
}
$_SESSION['mensaje_evaluaciones'] = ['texto' => 'Solo las cuentas de estudiante pueden unirse a una clase con código.', 'tipo' => 'warning'];
header('Location: ' . (dashboardPath($usuario['rol']) ?? 'index.html'), true, 303);
exit;
