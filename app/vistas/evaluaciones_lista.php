<?php
// Vista de "Mis evaluaciones". Variables: $evaluaciones, $mensaje, $porEliminar.
$csrf = escapar(csrfToken());
?>
<?php if ($mensaje !== null): ?>
    <div class="alert alert-<?= escapar(in_array($mensaje['tipo'], ['success', 'info', 'warning', 'danger'], true) ? $mensaje['tipo'] : 'info') ?>" role="status">
        <?= escapar($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<?php if ($porEliminar !== null): ?>
    <section class="alert alert-danger" role="alertdialog" aria-labelledby="confirmar-titulo" aria-describedby="confirmar-texto">
        <h2 class="h5" id="confirmar-titulo">¿Eliminar esta evaluación?</h2>
        <p id="confirmar-texto">Vas a eliminar <strong><?= escapar($porEliminar['titulo']) ?></strong> junto con sus preguntas. Esta acción no se puede deshacer.</p>
        <form method="post" action="evaluaciones.php" class="d-flex flex-wrap gap-2">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= (int)$porEliminar['id'] ?>">
            <button class="btn btn-danger" type="submit" autofocus>Sí, eliminar</button>
            <a class="btn btn-outline-secondary" href="evaluaciones.php">Cancelar</a>
        </form>
    </section>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="mb-0 text-secondary">Solo ves las evaluaciones que has creado tú.</p>
    <a class="btn btn-primary" href="evaluacion_form.php"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva evaluación</a>
</div>

<?php if (!$evaluaciones): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-journal-plus"></i></span>
        <h2>Aún no tienes evaluaciones</h2>
        <p>Crea la primera: se guardará como borrador y podrás publicarla cuando esté lista.</p>
        <a class="btn btn-primary btn-lg" href="evaluacion_form.php"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Crear mi primera evaluación</a>
    </section>
<?php else: ?>
    <div class="card-ui table-card table-responsive">
        <table class="table table-ui table-hover align-middle">
            <caption class="visually-hidden">Mis evaluaciones</caption>
            <thead>
            <tr>
                <th scope="col">Título</th>
                <th scope="col">Estado</th>
                <th scope="col">Asignada a</th>
                <th scope="col">Apertura</th>
                <th scope="col">Cierre</th>
                <th scope="col">Tiempo</th>
                <th scope="col">Intentos</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($evaluaciones as $fila): ?>
                <?php $publicada = (int)$fila['publicada'] === 1; $id = (int)$fila['id']; ?>
                <tr>
                    <th scope="row" class="titulo-evaluacion"><?= escapar($fila['titulo']) ?></th>
                    <td><span class="badge-estado <?= $publicada ? 'badge-publicada' : 'badge-borrador' ?>"><?= $publicada ? 'Publicada' : 'Borrador' ?></span></td>
                    <td class="text-nowrap">
                        <?php if ((int)$fila['total_clases'] > 0): ?>
                            <i class="bi bi-easel me-1 text-primary" aria-hidden="true"></i><?= (int)$fila['total_clases'] ?> clase<?= (int)$fila['total_clases'] === 1 ? '' : 's' ?>
                        <?php else: ?>
                            <span class="sin-asignar"><i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>Sin clase</span>
                        <?php endif; ?>
                        <span class="d-block small <?= (int)$fila['total_preguntas'] === 0 ? 'sin-asignar' : 'text-secondary' ?>"><?= (int)$fila['total_preguntas'] ?> pregunta<?= (int)$fila['total_preguntas'] === 1 ? '' : 's' ?></span>
                    </td>
                    <td class="text-nowrap"><?= escapar(formatoFecha($fila['apertura'])) ?></td>
                    <td class="text-nowrap"><?= escapar(formatoFecha($fila['cierre'])) ?></td>
                    <td class="text-nowrap"><?= (int)$fila['tiempo_minutos'] ?> min</td>
                    <td><?= (int)$fila['intentos_permitidos'] ?></td>
                    <td>
                        <div class="acciones-evaluacion" aria-label="Acciones para <?= escapar($fila['titulo']) ?>" role="group">
                            <a class="btn btn-sm btn-outline-primary" href="evaluacion_form.php?id=<?= $id ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
                            <a class="btn btn-sm btn-outline-primary" href="preguntas.php?evaluacion=<?= $id ?>"><i class="bi bi-list-check me-1" aria-hidden="true"></i>Preguntas</a>
                            <form method="post" action="evaluaciones.php">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button class="btn btn-sm btn-outline-primary" name="accion" value="duplicar" type="submit"><i class="bi bi-copy me-1" aria-hidden="true"></i>Duplicar</button>
                            </form>
                            <form method="post" action="evaluaciones.php">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button class="btn btn-sm <?= $publicada ? 'btn-outline-warning' : 'btn-success' ?>" name="accion"
                                        value="<?= $publicada ? 'despublicar' : 'publicar' ?>" type="submit">
                                    <i class="bi <?= $publicada ? 'bi-eye-slash' : 'bi-send' ?> me-1" aria-hidden="true"></i><?= $publicada ? 'Despublicar' : 'Publicar' ?>
                                </button>
                            </form>
                            <a class="btn btn-sm btn-outline-danger" href="evaluaciones.php?eliminar=<?= $id ?>"><i class="bi bi-trash me-1" aria-hidden="true"></i>Eliminar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
