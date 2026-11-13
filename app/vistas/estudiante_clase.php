<?php
// Clase del estudiante. Variables: $clase, $evaluaciones, $mensaje.
?>
<p><a class="btn btn-sm btn-outline-primary" href="clases.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Mis clases</a></p>
<section class="clase-banner mb-4" style="--clase-color: <?= escapar($clase['color']) ?>">
    <div>
        <p class="clase-banner-institucion"><i class="bi bi-building me-1" aria-hidden="true"></i><?= escapar($clase['institucion'] ?? 'Sin institución') ?> · <?= escapar($clase['docente']) ?></p>
        <?php if (trim((string)$clase['descripcion']) !== ''): ?><p class="clase-banner-descripcion"><?= nl2br(escapar($clase['descripcion'])) ?></p><?php endif; ?>
    </div>
</section>
<h2 class="card-title-ui mb-3"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Evaluaciones de la clase</h2>
<?php
$mostrarClases = false;
require __DIR__ . DIRECTORY_SEPARATOR . 'estudiante_evaluaciones.php';
