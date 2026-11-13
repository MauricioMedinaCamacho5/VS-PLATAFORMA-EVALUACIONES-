<?php
// Tarjetas de "Mis clases" del docente. Variables: $clases, $mensaje.
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<div class="d-flex flex-wrap justify-content-end mb-3">
    <a class="btn btn-primary" href="clase_form.php"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva clase</a>
</div>

<?php if (!$clases): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-easel"></i></span>
        <h2>Aún no tienes clases</h2>
        <p>Crea una clase, comparte su código con tus estudiantes y asígnale evaluaciones.</p>
        <a class="btn btn-primary btn-lg" href="clase_form.php"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Crear mi primera clase</a>
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
                        <span><i class="bi bi-people me-1" aria-hidden="true"></i><?= (int)$clase['estudiantes'] ?> estudiante<?= (int)$clase['estudiantes'] === 1 ? '' : 's' ?></span>
                        <span><i class="bi bi-clipboard-check me-1" aria-hidden="true"></i><?= (int)$clase['evaluaciones'] ?> evaluaci<?= (int)$clase['evaluaciones'] === 1 ? 'ón' : 'ones' ?></span>
                    </span>
                    <span class="clase-card-footer">
                        <?php if ($clase['codigo_union'] !== null && (int)$clase['codigo_activo'] === 1): ?>
                            <span class="codigo-mini"><i class="bi bi-key me-1" aria-hidden="true"></i><?= escapar($clase['codigo_union']) ?></span>
                        <?php else: ?>
                            <span class="codigo-mini codigo-inactivo"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>Código desactivado</span>
                        <?php endif; ?>
                        <span class="clase-card-ir">Abrir <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
