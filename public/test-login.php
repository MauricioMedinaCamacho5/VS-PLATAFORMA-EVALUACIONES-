<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Esta es una página de PRUEBA SOLO para desarrollo local
// NO usar en producción

$pdo = db();
$stmt = $pdo->query('SELECT u.id, u.nombre, u.correo, r.nombre as rol FROM usuarios u JOIN roles r ON r.id = u.rol_id LIMIT 10');
$usuarios = $stmt->fetchAll();

// Si viene con ?user_id, hacer login automático
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $stmt = $pdo->prepare('SELECT u.id, u.nombre, u.correo, r.nombre as rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?');
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Simular login
        if (!session_regenerate_id(true)) {
            die('No se pudo regenerar la sesión.');
        }
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        
        // Redirigir al perfil según el rol
        $perfil_path = match($usuario['rol']) {
            'administrador' => '/evaluaciones/public/admin/perfil.php',
            'docente' => '/evaluaciones/public/docente/perfil.php',
            'estudiante' => '/evaluaciones/public/estudiante/perfil.php',
            default => '/login.html'
        };
        
        header('Location: http://localhost' . $perfil_path);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Login - Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 600px; }
        .card { box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: none; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .user-btn { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; text-align: left; justify-content: space-between; padding: 12px; border: 1px solid #ddd; border-radius: 8px; transition: all 0.3s; }
        .user-btn:hover { background: #f0f0f0; border-color: #667eea; transform: translateX(5px); }
        .role-badge { font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="mb-0"><i class="bi bi-lock"></i> Login Rápido - Testing</h2>
            <small>Solo para desarrollo local</small>
        </div>
        <div class="card-body">
            <p class="text-muted">Haz clic en un usuario para entrar automáticamente a su perfil:</p>
            <div class="list-group">
                <?php foreach ($usuarios as $user): ?>
                    <a href="?user_id=<?= $user['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($user['nombre']) ?></strong>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($user['correo']) ?></small>
                        </div>
                        <span class="badge bg-primary"><?= ucfirst($user['rol']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <hr>
            <p class="text-center text-muted small">
                <a href="/login.html">Volver a login normal</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
