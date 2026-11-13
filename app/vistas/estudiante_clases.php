<?php
// Tarjetas de clases del estudiante. Variables: $clases, $mensaje.
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<form class="card-ui unirse-rapido mb-4" method="post" action="unirse.php">
    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
    <span class="qa-icon" aria-hidden="true"><i class="bi bi-plus-circle"></i></span>
    <div class="flex-grow-1">
        <label class="form-label mb-1" for="codigo-union"><strong>Unirme a una clase</strong></label>
        <p class="small text-secondary mb-2">Escribe el código de 6 caracteres que te dio tu docente.</p>
        <div class="d-flex flex-wrap gap-2">
            <input class="form-control input-codigo" id="codigo-union" name="codigo" maxlength="8" required
                   placeholder="AB12CD" autocomplete="off" autocapitalize="characters" spellcheck="false">
            <button class="btn btn-primary" type="submit"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Unirme</button>
        </div>
    </div>
</form>

<?php if (!$clases): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-easel"></i></span>
        <h2>Todavía no estás en ninguna clase</h2>
        <p>Pídele a tu docente el código o el enlace de la clase y escríbelo arriba.</p>
    </section>
<?php else: ?>
    <div class="row g-3 g-lg-4">
        <?php foreach ($clases as $clase): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="clase-card card-hover" href="clase.php?id=<?= (int)$clase['id'] ?>" style="--clase-color: <?= escapar($clase['color']) ?>">
                    <span class="clase-card-banner">
                        <span class="clase-card-nombre"><?= escapar($clase['nombre']) ?></span>
                        <span class="clase-card-institucion"><?= escapar($clase['institucion'] ?? 'Sin institución') ?></span>
                    </span>
                    <span class="clase-card-body">
                        <span><i class="bi bi-person-badge me-1" aria-hidden="true"></i><?= escapar($clase['docente']) ?></span>
                    </span>
                    <span class="clase-card-footer">
                        <?php if ((int)$clase['disponibles'] > 0): ?>
                            <span class="badge-estado badge-publicada"><?= (int)$clase['disponibles'] ?> disponible<?= (int)$clase['disponibles'] === 1 ? '' : 's' ?></span>
                        <?php else: ?>
                            <span class="small text-secondary">Sin evaluaciones abiertas</span>
                        <?php endif; ?>
                        <span class="clase-card-ir">Entrar <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
