<?php
// Inicio del estudiante. Variables: $estadisticas, $disponibles, $mensaje.
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    <a class="btn btn-primary" href="clases.php#codigo-union"><i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Unirme a una clase</a>
</div>
<section aria-label="Resumen">
    <div class="row g-3 g-lg-4 mb-4">
        <?php foreach ($estadisticas as [$etiqueta, $valor, $icono, $color]): ?>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card-ui card-hover stat-card <?= escapar($color) ?>">
                    <span class="stat-icon" aria-hidden="true"><i class="bi <?= escapar($icono) ?>"></i></span>
                    <div>
                        <span class="stat-value"><?= (int)$valor ?></span>
                        <span class="stat-label"><?= escapar($etiqueta) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!$disponibles): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-cup-hot"></i></span>
        <h2>No tienes evaluaciones disponibles ahora</h2>
        <p>Entra a tus clases para ver las próximas, o únete a una nueva con el código de tu docente.</p>
        <a class="btn btn-primary btn-lg" href="clases.php"><i class="bi bi-easel me-2" aria-hidden="true"></i>Ver mis clases</a>
    </section>
<?php else: ?>
    <section class="card-ui table-card" aria-labelledby="disponibles-titulo">
        <div class="section-header">
            <h2 class="card-title-ui" id="disponibles-titulo"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>Disponibles para presentar</h2>
            <a class="btn btn-sm btn-outline-primary" href="evaluaciones.php">Ver todas <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-ui table-hover align-middle">
                <thead>
                <tr>
                    <th scope="col">Evaluación</th>
                    <th scope="col" class="d-none d-md-table-cell">Cierre</th>
                    <th scope="col" class="d-none d-sm-table-cell">Intentos</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($disponibles as $fila): ?>
                    <tr>
                        <th scope="row" class="titulo-evaluacion">
                            <?= escapar($fila['titulo']) ?>
                            <span class="d-block fw-normal small text-secondary mt-1"><?= escapar($fila['docente']) ?><span class="d-md-none"> · cierra <?= escapar(formatoFecha($fila['cierre'])) ?></span></span>
                        </th>
                        <td class="text-nowrap text-secondary d-none d-md-table-cell"><?= escapar(formatoFecha($fila['cierre'])) ?></td>
                        <td class="d-none d-sm-table-cell"><?= (int)$fila['intentos_usados'] ?> de <?= (int)$fila['intentos_permitidos'] ?></td>
                        <td class="text-end">
                            <?php if ($fila['intento_en_curso'] !== null): ?>
                                <a class="btn btn-sm btn-primary" href="examen.php?intento=<?= (int)$fila['intento_en_curso'] ?>"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Continuar</a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-primary" href="presentar.php?id=<?= (int)$fila['id'] ?>" aria-label="Presentar <?= escapar($fila['titulo']) ?>"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Presentar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
