<?php
declare(strict_types=1);

// Unirse a una clase: GET muestra la clase del código (para confirmar), POST une al estudiante.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
$usuario = requerirRol('estudiante');
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrfFormulario();
    try {
        $resultado = unirEstudianteAClase($db, (int)$usuario['id'], $_POST['codigo'] ?? null);
    } catch (Throwable $exception) {
        error_log('Error al unirse a una clase: ' . $exception);
        http_response_code(500);
        exit('No se pudo completar la unión. Inténtalo más tarde.');
    }
    if (!$resultado['ok']) {
        redirigirConMensaje('clases.php', $resultado['mensaje'], $resultado['tipo']);
    }
    redirigirConMensaje('clase.php?id=' . (int)$resultado['clase']['id'], $resultado['mensaje'], $resultado['tipo']);
}

$codigo = normalizarCodigo($_GET['codigo'] ?? null);
if ($codigo === null) {
    redirigirConMensaje('clases.php', isset($_GET['codigo']) ? 'El enlace no tiene un código válido.' : 'Escribe el código de la clase.', isset($_GET['codigo']) ? 'danger' : 'info');
}
try {
    $clase = claseActivaPorCodigo($db, $codigo);
    $yaMiembro = $clase !== null && buscarClaseDelEstudiante($db, (int)$clase['id'], (int)$usuario['id']) !== null;
} catch (Throwable $exception) {
    error_log('Error al consultar el código de clase: ' . $exception);
    http_response_code(500);
    exit('No se pudo consultar la clase. Inténtalo más tarde.');
}
if ($clase === null) {
    redirigirConMensaje('clases.php', 'Ese código no existe o el docente lo desactivó. Pídele uno nuevo.', 'danger');
}
if ($yaMiembro) {
    redirigirConMensaje('clase.php?id=' . (int)$clase['id'], 'Ya formas parte de «' . $clase['nombre'] . '».', 'info');
}

$titulo = 'Unirme a una clase';
$seccion = 'clases';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_unirse.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
