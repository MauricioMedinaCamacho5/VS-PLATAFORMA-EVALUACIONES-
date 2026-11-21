<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Solo usuarios autenticados
$usuario = currentUser();
if ($usuario === null) {
    header('Location: ../login.html');
    exit;
}

$usuario_id = (int)$usuario['id'];
$pdo = db();

// Obtener foto_perfil si no está en currentUser
$stmt = $pdo->prepare('SELECT foto_perfil FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$fotoData = $stmt->fetch(PDO::FETCH_ASSOC);
if ($fotoData) {
    $usuario['foto_perfil'] = $fotoData['foto_perfil'];
}

$nomRol = ucfirst($usuario['rol']);
$mensaje = null;
$error = null;

// Ruta base de fotos (en public/fotos_perfil)
$fotosDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fotos_perfil';

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCsrf($_POST['csrf'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $accion = $_POST['accion'] ?? 'guardar';

        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');
            if (mb_strlen($nombre, 'UTF-8') < 2 || mb_strlen($nombre, 'UTF-8') > 150) {
                $error = 'El nombre debe tener entre 2 y 150 caracteres.';
            } else {
                $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ? WHERE id = ?');
                if ($stmt->execute([$nombre, $usuario_id])) {
                    $_SESSION['usuario_nombre'] = $nombre;
                    $usuario['nombre'] = $nombre;
                    $mensaje = 'Perfil actualizado correctamente.';
                } else {
                    $error = 'No se pudo guardar los cambios.';
                }
            }
        } elseif ($accion === 'eliminar_foto') {
            if (!empty($usuario['foto_perfil'])) {
                $rutaFoto = $fotosDir . DIRECTORY_SEPARATOR . basename($usuario['foto_perfil']);
                if (file_exists($rutaFoto)) @unlink($rutaFoto);

                $stmt = $pdo->prepare('UPDATE usuarios SET foto_perfil = NULL WHERE id = ?');
                if ($stmt->execute([$usuario_id])) {
                    $usuario['foto_perfil'] = null;
                    $mensaje = 'Foto de perfil eliminada.';
                } else {
                    $error = 'No se pudo eliminar la foto.';
                }
            }
        }
    }
}

// Procesar subida de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    if (!verificarCsrf($_POST['csrf'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $archivo = $_FILES['foto'];
        $errores = [];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'Error al subir el archivo.';
        }
        if (!in_array($archivo['type'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $errores[] = 'Solo se aceptan JPG, PNG o WebP.';
        }
        if ($archivo['size'] > 2 * 1024 * 1024) {
            $errores[] = 'El archivo no debe superar 2 MB.';
        }

        if (empty($errores)) {
            if (!is_dir($fotosDir)) mkdir($fotosDir, 0755, true);

            $ext = match($archivo['type']) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };
            $nombreArchivo = 'perfil_' . $usuario_id . '_' . time() . '.' . $ext;
            $rutaDestino = $fotosDir . DIRECTORY_SEPARATOR . $nombreArchivo;

            if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                // Eliminar foto anterior si existe
                if (!empty($usuario['foto_perfil'])) {
                    $rutaAnterior = $fotosDir . DIRECTORY_SEPARATOR . basename($usuario['foto_perfil']);
                    if (file_exists($rutaAnterior)) @unlink($rutaAnterior);
                }

                $rutaGuardar = 'fotos_perfil/' . $nombreArchivo;
                $stmt = $pdo->prepare('UPDATE usuarios SET foto_perfil = ? WHERE id = ?');
                if ($stmt->execute([$rutaGuardar, $usuario_id])) {
                    $usuario['foto_perfil'] = $rutaGuardar;
                    $mensaje = 'Foto de perfil actualizada correctamente.';
                } else {
                    @unlink($rutaDestino);
                    $error = 'No se pudo guardar la foto en la base de datos.';
                }
            } else {
                $error = 'No se pudo guardar el archivo.';
            }
        } else {
            $error = implode(' ', $errores);
        }
    }
}

$titulo = 'Mi perfil';
$vista = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas' . DIRECTORY_SEPARATOR . 'perfil_vista.php';
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
