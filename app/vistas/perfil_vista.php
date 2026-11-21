<?php
// Perfil del usuario: edición de nombre y foto de perfil. Variable: $usuario.
?>
<div class="row g-4">
    <div class="col-lg-4">
        <section class="card-ui" aria-labelledby="foto-titulo">
            <h2 class="card-title-ui" id="foto-titulo"><i class="bi bi-image" aria-hidden="true"></i>Foto de perfil</h2>
            <div class="perfil-foto-container">
                <div class="perfil-foto" id="foto-preview" aria-hidden="true">
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="/<?= escapar($usuario['foto_perfil']) ?>" alt="Foto de <?= escapar($usuario['nombre']) ?>" class="perfil-foto-img">
                    <?php else: ?>
                        <div class="perfil-foto-placeholder">
                            <div class="perfil-avatar"><?= escapar(iniciales($usuario['nombre'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <form id="formulario-foto" method="post" action="perfil.php" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                    <input type="file" id="archivo-foto" name="foto" accept="image/jpeg,image/png,image/webp" required class="form-control mb-2">
                    <small class="text-secondary d-block mb-2">JPG, PNG o WebP · Máximo 2 MB</small>
                    <button class="btn btn-primary btn-sm w-100" type="submit" id="boton-subir"><i class="bi bi-upload me-1" aria-hidden="true"></i>Subir foto</button>
                </form>
                <?php if (!empty($usuario['foto_perfil'])): ?>
                    <form method="post" action="perfil.php" class="mt-2">
                        <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                        <input type="hidden" name="accion" value="eliminar_foto">
                        <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar foto</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-8">
        <section class="card-ui" aria-labelledby="datos-titulo">
            <h2 class="card-title-ui" id="datos-titulo"><i class="bi bi-person-fill" aria-hidden="true"></i>Mis datos</h2>
            <form method="post" action="perfil.php">
                <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= escapar($usuario['nombre']) ?>" required>
                    <small class="text-secondary">Mínimo 2 caracteres.</small>
                </div>
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" value="<?= escapar($usuario['correo']) ?>" disabled>
                    <small class="text-secondary">No se puede cambiar el correo después del registro.</small>
                </div>
                <div class="mb-3">
                    <label for="rol" class="form-label">Rol</label>
                    <input type="text" class="form-control" id="rol" value="<?= escapar($nomRol) ?>" disabled>
                </div>
                <button class="btn btn-primary" type="submit" name="accion" value="guardar"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Guardar cambios</button>
            </form>
        </section>
    </div>
</div>

<style>
.perfil-foto-container { text-align: center; }
.perfil-foto {
    display: inline-block;
    width: 150px;
    height: 150px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid var(--borde);
    background: var(--fondo);
}
.perfil-foto-img { width: 100%; height: 100%; object-fit: cover; }
.perfil-foto-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}
.perfil-avatar {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--azul-claro), #2563EB);
    color: var(--blanco);
    font-size: 2.5rem;
    font-weight: 700;
}
</style>
