<?php
// Inicio del docente. Variables: $estadisticas, $ultimas, $mensaje.
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<section aria-label="Resumen">
    <div class="row g-3 g-lg-4 mb-4">
        <?php foreach ($estadisticas as [$etiqueta, $valor, $icono, $color]): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-ui card-hover stat-card <?= escapar($color) ?>">
                    <span class="stat-icon" aria-hidden="true"><i class="bi <?= escapar($icono) ?>"></i></span>
                    <div>
                        <span class="stat-value"><?= number_format($valor, 0, ',', '.') ?></span>
                        <span class="stat-label"><?= escapar($etiqueta) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-4" aria-labelledby="acciones-titulo">
    <div class="section-header">
        <h2 class="card-title-ui" id="acciones-titulo"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>Acciones rápidas</h2>
    </div>
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-md-4">
            <a class="quick-action qa-primary card-hover" href="evaluacion_form.php">
                <span class="qa-icon" aria-hidden="true"><i class="bi bi-plus-circle"></i></span>
                <span class="qa-text"><strong>Nueva evaluación</strong><small>Crea un borrador en segundos</small></span>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a class="quick-action card-hover" href="clase_form.php">
                <span class="qa-icon" aria-hidden="true"><i class="bi bi-easel"></i></span>
                <span class="qa-text"><strong>Nueva clase</strong><small>Con código para que se unan tus estudiantes</small></span>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a class="quick-action card-hover" href="ia.php">
                <span class="qa-icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
                <span class="qa-text"><strong>Generar con IA</strong><small>Desde un texto, un PDF o un tema</small></span>
            </a>
        </div>
    </div>
</section>

<?php if (!$ultimas): ?>
    <section class="card-ui empty-state" aria-labelledby="vacio-titulo">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-journal-plus"></i></span>
        <h2 id="vacio-titulo">Todavía no tienes evaluaciones</h2>
        <p>Crea tu primera evaluación: se guardará como borrador y podrás publicarla cuando esté lista.</p>
        <a class="btn btn-primary btn-lg" href="evaluacion_form.php"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Crear mi primera evaluación</a>
    </section>
<?php else: ?>
    <section class="card-ui table-card" aria-labelledby="ultimas-titulo">
        <div class="section-header">
            <h2 class="card-title-ui" id="ultimas-titulo"><i class="bi bi-clock-history" aria-hidden="true"></i>Mis últimas evaluaciones</h2>
            <a class="btn btn-sm btn-outline-primary" href="evaluaciones.php">Ver todas <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-ui table-hover align-middle">
                <thead>
                <tr>
                    <th scope="col">Título</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="d-none d-md-table-cell">Cierre</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimas as $fila): ?>
                    <?php $publicada = (int)$fila['publicada'] === 1; ?>
                    <tr>
                        <th scope="row" class="titulo-evaluacion">
                            <?= escapar($fila['titulo']) ?>
                            <span class="d-block d-md-none fw-normal small text-secondary mt-1">Cierra el <?= escapar(formatoFecha($fila['cierre'])) ?></span>
                        </th>
                        <td><span class="badge-estado <?= $publicada ? 'badge-publicada' : 'badge-borrador' ?>"><?= $publicada ? 'Publicada' : 'Borrador' ?></span></td>
                        <td class="text-nowrap text-secondary d-none d-md-table-cell"><i class="bi bi-calendar-event me-1" aria-hidden="true"></i><?= escapar(formatoFecha($fila['cierre'])) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="evaluacion_form.php?id=<?= (int)$fila['id'] ?>"
                               aria-label="Ver <?= escapar($fila['titulo']) ?>"><i class="bi bi-eye me-1" aria-hidden="true"></i>Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
