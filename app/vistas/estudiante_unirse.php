<?php
// Confirmación para unirse a una clase. Variables: $clase, $codigo.
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <section class="clase-card clase-card-estatica" style="--clase-color: <?= escapar($clase['color']) ?>" aria-labelledby="clase-nombre">
            <span class="clase-card-banner">
                <span class="clase-card-nombre" id="clase-nombre"><?= escapar($clase['nombre']) ?></span>
                <span class="clase-card-institucion"><?= escapar($clase['institucion'] ?? 'Sin institución') ?></span>
            </span>
            <span class="clase-card-body d-block">
                <span class="d-block mb-3"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>Docente: <strong><?= escapar($clase['docente']) ?></strong></span>
                <form method="post" action="unirse.php" class="d-flex flex-wrap gap-2">
                    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                    <input type="hidden" name="codigo" value="<?= escapar($codigo) ?>">
                    <button class="btn btn-primary" type="submit" autofocus><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Unirme a la clase</button>
                    <a class="btn btn-outline-secondary" href="clases.php">Cancelar</a>
                </form>
            </span>
        </section>
    </div>
</div>
