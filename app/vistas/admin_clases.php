<?php
// Administrador: clases, instituciones y docentes.
// Variables: $estadisticas, $clases, $instituciones, $docentes, $filtroId.
?>
<div class="row g-3 g-lg-4 mb-4">
    <?php foreach ($estadisticas as [$etiqueta, $valor, $icono, $color]): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-ui card-hover stat-card <?= escapar($color) ?>">
                <span class="stat-icon" aria-hidden="true"><i class="bi <?= escapar($icono) ?>"></i></span>
                <div><span class="stat-value"><?= (int)$valor ?></span><span class="stat-label"><?= escapar($etiqueta) ?></span></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<section class="card-ui table-card mb-4" aria-labelledby="clases-titulo">
    <div class="section-header">
        <h2 class="card-title-ui" id="clases-titulo"><i class="bi bi-easel" aria-hidden="true"></i>Clases</h2>
        <form class="d-flex align-items-center gap-2" method="get" action="clases.php">
            <label class="small text-secondary text-nowrap" for="filtro-institucion">Institución</label>
            <select class="form-select form-select-sm" id="filtro-institucion" name="institucion" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($instituciones as $institucion): ?>
                    <option value="<?= (int)$institucion['id'] ?>"<?= $filtroId === (int)$institucion['id'] ? ' selected' : '' ?>><?= escapar($institucion['nombre']) ?></option>
                <?php endforeach; ?>
                <option value="ninguna"<?= $filtroId === 'ninguna' ? ' selected' : '' ?>>Sin institución</option>
            </select>
            <noscript><button class="btn btn-sm btn-outline-primary" type="submit">Filtrar</button></noscript>
        </form>
    </div>
    <?php if (!$clases): ?>
        <p class="text-secondary px-4 pb-4 mb-0 mt-3">No hay clases<?= $filtroId !== null ? ' con este filtro' : ' todavía' ?>.</p>
    <?php else: ?>
        <div class="table-responsive mt-3">
            <table class="table table-ui table-hover align-middle">
                <thead>
                <tr>
                    <th scope="col">Clase</th>
                    <th scope="col">Institución</th>
                    <th scope="col">Docente</th>
                    <th scope="col">Estudiantes</th>
                    <th scope="col">Evaluaciones</th>
                    <th scope="col">Código</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clases as $clase): ?>
                    <tr>
                        <th scope="row" class="titulo-evaluacion" style="--clase-color: <?= escapar($clase['color']) ?>"><span class="punto-color" aria-hidden="true"></span><?= escapar($clase['nombre']) ?></th>
                        <td><?= escapar($clase['institucion'] ?? '—') ?></td>
                        <td><?= escapar($clase['docente']) ?></td>
                        <td><?= (int)$clase['estudiantes'] ?></td>
                        <td><?= (int)$clase['evaluaciones'] ?></td>
                        <td class="text-nowrap">
                            <?php if ($clase['codigo_union'] !== null && (int)$clase['codigo_activo'] === 1): ?>
                                <code class="codigo-tabla"><?= escapar($clase['codigo_union']) ?></code>
                            <?php else: ?>
                                <span class="text-secondary small">Desactivado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="row g-4">
    <div class="col-lg-6">
        <section class="card-ui table-card" aria-labelledby="inst-titulo">
            <div class="section-header"><h2 class="card-title-ui" id="inst-titulo"><i class="bi bi-building" aria-hidden="true"></i>Instituciones</h2></div>
            <?php if (!$instituciones): ?>
                <p class="text-secondary px-4 pb-4 mb-0 mt-3">Se crean al escribirlas en una clase.</p>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-ui align-middle">
                        <thead><tr><th scope="col">Nombre</th><th scope="col">Clases</th><th scope="col">Docentes</th></tr></thead>
                        <tbody>
                        <?php foreach ($instituciones as $institucion): ?>
                            <tr>
                                <th scope="row"><a href="clases.php?institucion=<?= (int)$institucion['id'] ?>"><?= escapar($institucion['nombre']) ?></a></th>
                                <td><?= (int)$institucion['clases'] ?></td>
                                <td><?= (int)$institucion['docentes'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="card-ui table-card" aria-labelledby="doc-titulo">
            <div class="section-header"><h2 class="card-title-ui" id="doc-titulo"><i class="bi bi-person-badge" aria-hidden="true"></i>Docentes</h2></div>
            <?php if (!$docentes): ?>
                <p class="text-secondary px-4 pb-4 mb-0 mt-3">No hay cuentas de docente.</p>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-ui align-middle">
                        <thead><tr><th scope="col">Docente</th><th scope="col">Clases</th><th scope="col">Evaluaciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($docentes as $docente): ?>
                            <tr>
                                <th scope="row"><?= escapar($docente['nombre']) ?><span class="d-block small text-secondary fw-normal"><?= escapar($docente['correo']) ?><?= (int)$docente['activo'] === 1 ? '' : ' · desactivado' ?></span></th>
                                <td><?= (int)$docente['clases'] ?></td>
                                <td><?= (int)$docente['evaluaciones'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
