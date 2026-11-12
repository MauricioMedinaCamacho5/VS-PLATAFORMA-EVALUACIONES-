<?php
declare(strict_types=1);

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escapar($titulo) ?> - Evaluaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="../assets/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <a class="sidebar-brand" href="dashboard.php">Evaluaciones</a>
        <nav aria-label="Menú principal">
            <a class="sidebar-link<?= !isset($usuarios) ? ' active' : '' ?>"<?= !isset($usuarios) ? ' aria-current="page"' : '' ?> href="dashboard.php">Inicio</a>
            <?php if ($usuario['rol'] === 'administrador'): ?>
                <a class="sidebar-link<?= isset($usuarios) ? ' active' : '' ?>"<?= isset($usuarios) ? ' aria-current="page"' : '' ?> href="usuarios.php">Gestionar roles</a>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="dashboard-main">
        <header class="topbar">
            <div>
                <span class="topbar-label">Sesión iniciada</span>
                <strong><?= escapar($usuario['nombre']) ?></strong>
            </div>
            <form action="../logout.php" method="post">
                <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                <button class="btn btn-outline-primary" type="submit">Cerrar sesión</button>
            </form>
        </header>
        <main class="content">
            <h1><?= escapar($titulo) ?></h1>
            <?php if (isset($usuarios)): ?>
            <?php if ($mensaje !== null): ?>
                <div class="role-message" role="status"><?= escapar($mensaje) ?></div>
            <?php endif; ?>
            <p>Selecciona un rol para cada cuenta. Debe permanecer al menos un administrador activo.</p>
            <div class="user-list">
                <?php foreach ($usuarios as $cuenta): ?>
                    <section class="info-card user-card">
                        <h2><?= escapar($cuenta['nombre']) ?></h2>
                        <p><?= escapar($cuenta['correo']) ?> · <?= (int)$cuenta['activo'] === 1 ? 'Activo' : 'Desactivado' ?></p>
                        <form class="role-form" method="post" action="usuarios.php">
                            <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                            <input type="hidden" name="usuario_id" value="<?= escapar((string)$cuenta['id']) ?>">
                            <label for="rol-<?= escapar((string)$cuenta['id']) ?>">Rol</label>
                            <select id="rol-<?= escapar((string)$cuenta['id']) ?>" name="rol" required>
                                <?php foreach (['estudiante', 'docente', 'administrador'] as $rol): ?>
                                    <option value="<?= escapar($rol) ?>"<?= $cuenta['rol'] === $rol ? ' selected' : '' ?>><?= escapar(ucfirst($rol)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-primary" type="submit">Guardar rol</button>
                        </form>
                    </section>
                <?php endforeach; ?>
                <?php if (!$usuarios): ?><p>No hay usuarios en esta página.</p><?php endif; ?>
            </div>
            <nav class="pagination-links" aria-label="Páginas de usuarios">
                <?php if ($pagina > 1): ?><a href="?pagina=<?= $pagina - 1 ?>">Anterior</a><?php endif; ?>
                <span>Página <?= $pagina ?></span>
                <?php if ($haySiguiente): ?><a href="?pagina=<?= $pagina + 1 ?>">Siguiente</a><?php endif; ?>
            </nav>
            <?php else: ?>
            <p>Bienvenido, <?= escapar($usuario['nombre']) ?>.</p>
            <section class="info-card" aria-labelledby="estado-title">
                <h2 id="estado-title">Tu espacio de trabajo</h2>
                <p><?= escapar($descripcion) ?></p>
                <p class="pending">Los demás módulos están en desarrollo.</p>
            </section>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>
