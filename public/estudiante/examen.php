<?php
declare(strict_types=1);

// Examen en curso: GET muestra las preguntas con temporizador; POST envía y califica.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'examen.php';
$usuario = requerirRol('estudiante');
$estudianteId = (int)$usuario['id'];
$db = db();

$metodo = $_SERVER['REQUEST_METHOD'];
$intentoId = leerId($metodo === 'POST' ? ($_POST['intento'] ?? null) : ($_GET['intento'] ?? null));
if ($intentoId === null) {
    redirigirConMensaje('evaluaciones.php', 'El intento solicitado no es válido.', 'danger');
}

if ($metodo === 'POST') {
    verificarCsrfFormulario();
    try {
        [$enviado, $texto] = enviarIntento($db, $intentoId, $estudianteId, $_POST['respuestas'] ?? [], ($_POST['automatico'] ?? '') === '1');
    } catch (Throwable $exception) {
        error_log('Error al enviar el intento: ' . $exception);
        http_response_code(500);
        exit('No se pudo enviar la evaluación. Tus respuestas guardadas se conservan; vuelve a intentarlo.');
    }
    redirigirConMensaje('resultado.php?intento=' . $intentoId, $texto, $enviado ? 'success' : 'info');
}

try {
    $intento = intentoDelEstudiante($db, $intentoId, $estudianteId);
    if ($intento === null) {
        redirigirConMensaje('evaluaciones.php', 'Ese intento no existe.', 'danger');
    }
    if ($intento['estado'] !== 'en_curso') {
        header('Location: resultado.php?intento=' . $intentoId, true, 303);
        exit;
    }
    if ((int)$intento['segundos_restantes'] < -GRACIA_SEGUNDOS) {
        enviarIntento($db, $intentoId, $estudianteId, null, true);
        redirigirConMensaje('resultado.php?intento=' . $intentoId, 'El tiempo se agotó: se calificaron tus respuestas guardadas.', 'warning');
    }
    $preguntas = preguntasParaPresentar($db, $intento);
} catch (Throwable $exception) {
    error_log('Error al abrir el examen: ' . $exception);
    http_response_code(500);
    exit('No se pudo abrir la evaluación. Inténtalo más tarde.');
}

header('Cache-Control: no-store, private');
$titulo = $intento['titulo'];
$seccion = 'evaluaciones';
$scripts = ['../assets/js/examen.js'];
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'estudiante_examen.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
