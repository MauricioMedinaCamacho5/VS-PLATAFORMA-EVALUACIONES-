<?php
declare(strict_types=1);

// Plantilla común de los paneles (administrador, docente y estudiante).
// Variables: $usuario, $titulo y, según la página, $subtitulo, $seccion, $vista, $descripcion
// o los datos de gestión de roles ($usuarios, $mensaje, $pagina, $haySiguiente).

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Iniciales para el avatar: primera letra de las dos primeras palabras ("Mauricio Medina" → "MM"). */
function iniciales(string $nombre): string
{
    $palabras = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
    $letras = mb_substr($palabras[0], 0, 1, 'UTF-8');
    if (isset($palabras[1])) {
        $letras .= mb_substr($palabras[1], 0, 1, 'UTF-8');
    }
    return mb_strtoupper($letras, 'UTF-8');
}

$seccion = $seccion ?? (isset($usuarios) ? 'usuarios' : 'inicio');

// [clave, enlace (null = próximamente), texto, ícono de Bootstrap Icons, ícono activo opcional (por defecto «-fill»)]
$enlacesMenu = match ($usuario['rol']) {
    'administrador' => [
        ['inicio', 'dashboard.php', 'Inicio', 'bi-house-door'],
        ['usuarios', 'usuarios.php', 'Gestionar roles', 'bi-people'],
        ['clases', 'clases.php', 'Clases', 'bi-easel'],
    ],
    'docente' => [
        ['inicio', 'dashboard.php', 'Inicio', 'bi-house-door'],
        ['clases', 'clases.php', 'Mis clases', 'bi-easel'],
        ['evaluaciones', 'evaluaciones.php', 'Mis evaluaciones', 'bi-clipboard-check'],
        ['ia', 'ia.php', 'Generar con IA', 'bi-stars', 'bi-stars'],
        ['banco', null, 'Banco de preguntas', 'bi-file-earmark-text'],
        ['resultados', null, 'Resultados', 'bi-bar-chart-line'],
    ],
    default => [
        ['inicio', 'dashboard.php', 'Inicio', 'bi-house-door'],
        ['clases', 'clases.php', 'Mis clases', 'bi-easel'],
        ['evaluaciones', 'evaluaciones.php', 'Mis evaluaciones', 'bi-clipboard-check'],
        ['resultados', null, 'Mis resultados', 'bi-bar-chart-line'],
    ],
};
$nombresRol = ['administrador' => 'Administrador', 'docente' => 'Docente', 'estudiante' => 'Estudiante'];
$rolVisible = $nombresRol[$usuario['rol']] ?? ucfirst($usuario['rol']);

// Saludo y fecha iniciales (hora de Colombia); panel.js los ajusta a la hora del equipo del usuario.
$ahora = new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
$hora = (int)$ahora->format('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaHoy = ucfirst($dias[(int)$ahora->format('w')]) . ', ' . $ahora->format('j') . ' de '
    . $meses[(int)$ahora->format('n') - 1] . ' de ' . $ahora->format('Y');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E3A8A">
    <title><?= escapar($titulo) ?> - Evaluaciones</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/estilos.css" rel="stylesheet">
    <script src="../assets/js/panel.js" defer></script>
    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= escapar($script) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body>
<a class="visually-hidden-focusable btn btn-primary position-absolute m-2" style="z-index: 100" href="#contenido">Saltar al contenido</a>
<div class="app">
    <aside class="sidebar" id="sidebar" aria-label="Menú lateral">
        <a class="sidebar-brand" href="dashboard.php">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></span>
            <span>Evaluaciones</span>
        </a>
        <button class="sidebar-close" id="sidebar-close" type="button" aria-label="Cerrar menú">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>

        <nav class="menu" aria-label="Menú principal">
            <span class="menu-titulo">Menú</span>
            <?php foreach ($enlacesMenu as $menuItem): ?>
                <?php [$menuClave, $menuEnlace, $menuTexto, $menuIcono] = $menuItem; $menuIconoActivo = $menuItem[4] ?? $menuIcono . '-fill'; ?>
                <?php if ($menuEnlace === null): ?>
                    <span class="menu-link is-disabled" aria-disabled="true">
                        <i class="bi <?= escapar($menuIcono) ?>" aria-hidden="true"></i>
                        <span><?= escapar($menuTexto) ?></span>
                        <span class="badge-pronto">Pronto</span>
                    </span>
                <?php else: ?>
                    <a class="menu-link<?= $seccion === $menuClave ? ' active' : '' ?>"<?= $seccion === $menuClave ? ' aria-current="page"' : '' ?> href="<?= escapar($menuEnlace) ?>">
                        <i class="bi <?= escapar($seccion === $menuClave ? $menuIconoActivo : $menuIcono) ?>" aria-hidden="true"></i>
                        <span><?= escapar($menuTexto) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-user">
            <span class="avatar" aria-hidden="true"><?= escapar(iniciales($usuario['nombre'])) ?></span>
            <span class="sidebar-user-info">
                <span class="sidebar-user-name"><?= escapar($usuario['nombre']) ?></span>
                <span class="sidebar-user-role"><?= escapar($rolVisible) ?></span>
            </span>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

    <div class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menu-toggle" type="button" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div class="topbar-greeting">
                <span class="greeting-label" id="greeting"><?= escapar($saludo) ?></span>
                <span class="greeting-name"><?= escapar($usuario['nombre']) ?></span>
            </div>
            <div class="topbar-actions">
                <span class="topbar-date">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <time id="today" datetime="<?= escapar($ahora->format('Y-m-d')) ?>"><?= escapar($fechaHoy) ?></time>
                </span>
                <form action="../logout.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                    <button class="btn btn-outline-primary btn-logout" type="submit">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="content" id="contenido" tabindex="-1">
            <div class="page-header">
                <h1><?= escapar($titulo) ?></h1>
                <?php if (!empty($subtitulo)): ?><p class="page-subtitle"><?= escapar($subtitulo) ?></p><?php endif; ?>
            </div>

            <?php if (isset($vista)): ?>
                <?php require $vista; ?>
            <?php elseif (isset($usuarios)): ?>
                <?php if ($mensaje !== null): ?>
                    <div class="alert alert-info d-flex align-items-center gap-2" role="status">
                        <i class="bi bi-info-circle-fill" aria-hidden="true"></i><span><?= escapar($mensaje) ?></span>
                    </div>
                <?php endif; ?>
                <p class="text-secondary">Selecciona un rol para cada cuenta. Debe permanecer al menos un administrador activo.</p>
                <div class="user-list">
                    <?php foreach ($usuarios as $cuenta): ?>
                        <?php $idCuenta = escapar((string)$cuenta['id']); $activa = (int)$cuenta['activo'] === 1; ?>
                        <section class="info-card user-card card-hover">
                            <div class="user-card-info">
                                <span class="avatar" aria-hidden="true"><?= escapar(iniciales($cuenta['nombre'])) ?></span>
                                <div>
                                    <h2><?= escapar($cuenta['nombre']) ?></h2>
                                    <p><?= escapar($cuenta['correo']) ?> ·
                                        <span class="<?= $activa ? 'text-success' : 'text-danger' ?>"><?= $activa ? 'Activo' : 'Desactivado' ?></span></p>
                                </div>
                            </div>
                            <form class="role-form" method="post" action="usuarios.php">
                                <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                                <input type="hidden" name="usuario_id" value="<?= $idCuenta ?>">
                                <label class="visually-hidden" for="rol-<?= $idCuenta ?>">Rol de <?= escapar($cuenta['nombre']) ?></label>
                                <select class="form-select" id="rol-<?= $idCuenta ?>" name="rol" required>
                                    <?php foreach (['estudiante', 'docente', 'administrador'] as $rol): ?>
                                        <option value="<?= escapar($rol) ?>"<?= $cuenta['rol'] === $rol ? ' selected' : '' ?>><?= escapar(ucfirst($rol)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Guardar rol</button>
                            </form>
                        </section>
                    <?php endforeach; ?>
                    <?php if (!$usuarios): ?><p>No hay usuarios en esta página.</p><?php endif; ?>
                </div>
                <nav class="pagination-links" aria-label="Páginas de usuarios">
                    <?php if ($pagina > 1): ?><a class="btn btn-outline-primary btn-sm" href="?pagina=<?= $pagina - 1 ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i> Anterior</a><?php endif; ?>
                    <span class="text-secondary">Página <?= $pagina ?></span>
                    <?php if ($haySiguiente): ?><a class="btn btn-outline-primary btn-sm" href="?pagina=<?= $pagina + 1 ?>">Siguiente <i class="bi bi-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
                </nav>
            <?php else: ?>
                <?php
                // Panel genérico (administrador): muestra el mensaje de una sola vez si lo hay.
                $mensaje = is_array($_SESSION['mensaje_evaluaciones'] ?? null) ? $_SESSION['mensaje_evaluaciones'] : null;
                unset($_SESSION['mensaje_evaluaciones']);
                require __DIR__ . DIRECTORY_SEPARATOR . 'vistas' . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
                ?>
                <section class="info-card card-hover empty-state" aria-labelledby="estado-title">
                    <span class="empty-icon" aria-hidden="true"><i class="bi bi-rocket-takeoff"></i></span>
                    <h2 id="estado-title">Tu espacio de trabajo</h2>
                    <p><?= escapar($descripcion) ?></p>
                    <span class="badge-pronto">Más módulos en desarrollo</span>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>
