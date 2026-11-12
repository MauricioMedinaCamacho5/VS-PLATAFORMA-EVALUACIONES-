<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
$usuario = requerirRol('administrador');
$conexion = db();
$rolesPermitidos = ['estudiante', 'docente', 'administrador'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals(csrfToken(), $csrf)) {
        http_response_code(403);
        exit('La sesión del formulario ha vencido. Recarga la página.');
    }

    $id = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $rol = $_POST['rol'] ?? null;
    if ($id === false || $id === null || !is_string($rol) || !in_array($rol, $rolesPermitidos, true)) {
        $_SESSION['mensaje_roles'] = 'Selecciona un usuario y un rol válidos.';
        header('Location: usuarios.php', true, 303);
        exit;
    }

    try {
        $conexion->beginTransaction();
        // Serializa los cambios de rol para proteger al último administrador activo.
        $bloqueo = $conexion->prepare("SELECT id FROM roles WHERE nombre = 'administrador' FOR UPDATE");
        $bloqueo->execute();
        if ($bloqueo->fetch() === false) {
            throw new RuntimeException('Falta el rol administrador.');
        }
        $actor = $conexion->prepare(
            "SELECT u.id FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE u.id = ? AND u.activo = 1 AND r.nombre = 'administrador' FOR UPDATE"
        );
        $actor->execute([$usuario['id']]);
        if ($actor->fetch() === false) {
            $conexion->rollBack();
            $_SESSION['mensaje_acceso'] = 'Ya no tienes permiso para administrar roles.';
            header('Location: ../index.html', true, 303);
            exit;
        }
        $consulta = $conexion->prepare(
            'SELECT u.id, u.activo, r.nombre AS rol FROM usuarios u
             JOIN roles r ON r.id = u.rol_id WHERE u.id = ? FOR UPDATE'
        );
        $consulta->execute([$id]);
        $cuenta = $consulta->fetch();
        $nuevoRol = $conexion->prepare('SELECT id FROM roles WHERE nombre = ?');
        $nuevoRol->execute([$rol]);
        $rolId = $nuevoRol->fetchColumn();

        if ($cuenta === false || $rolId === false) {
            $mensaje = 'El usuario o el rol seleccionado no existe.';
        } elseif ($cuenta['rol'] === $rol) {
            $mensaje = 'El usuario ya tiene ese rol.';
        } else {
            $puedeCambiar = true;
            if ($cuenta['rol'] === 'administrador' && (int)$cuenta['activo'] === 1 && $rol !== 'administrador') {
                $total = $conexion->query(
                    "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
                     WHERE r.nombre = 'administrador' AND u.activo = 1"
                )->fetchColumn();
                if ((int)$total <= 1) {
                    $puedeCambiar = false;
                }
            }
            if (!$puedeCambiar) {
                $mensaje = 'No se puede cambiar el rol del último administrador activo.';
            } else {
                $actualizar = $conexion->prepare('UPDATE usuarios SET rol_id = ? WHERE id = ?');
                $actualizar->execute([$rolId, $id]);
                $mensaje = 'Rol actualizado correctamente.';
            }
        }
        $conexion->commit();
    } catch (Throwable $exception) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        error_log('Error al cambiar el rol: ' . $exception);
        http_response_code(500);
        exit('No se pudo actualizar el rol. Inténtalo más tarde.');
    }

    if ($id === (int)$usuario['id'] && $rol !== 'administrador' && $mensaje === 'Rol actualizado correctamente.') {
        $_SESSION['mensaje_acceso'] = 'Tu rol cambió. Entra a tu nuevo panel.';
        header('Location: ../index.html', true, 303);
        exit;
    }
    $_SESSION['mensaje_roles'] = $mensaje;
    header('Location: usuarios.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido.');
}

$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
if ($pagina === false) {
    http_response_code(400);
    exit('Página no válida.');
}
$pagina = $pagina ?? 1;
try {
    $consulta = $conexion->prepare(
        'SELECT u.id, u.nombre, u.correo, u.activo, r.nombre AS rol
         FROM usuarios u JOIN roles r ON r.id = u.rol_id ORDER BY u.id LIMIT 31 OFFSET :inicio'
    );
    $consulta->bindValue(':inicio', ($pagina - 1) * 30, PDO::PARAM_INT);
    $consulta->execute();
    $usuarios = $consulta->fetchAll();
    $haySiguiente = count($usuarios) > 30;
    if ($haySiguiente) {
        array_pop($usuarios);
    }
} catch (Throwable $exception) {
    error_log('Error al consultar usuarios: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar los usuarios. Inténtalo más tarde.');
}
$mensaje = $_SESSION['mensaje_roles'] ?? null;
unset($_SESSION['mensaje_roles']);
$titulo = 'Gestionar roles';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
