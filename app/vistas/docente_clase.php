<?php
// Detalle de clase (docente). Variables: $clase, $estudiantes, $evaluaciones, $codigoVisible, $enlaceUnion, $mensaje.
$csrf = escapar(csrfToken());
$idClase = (int)$clase['id'];
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<section class="clase-banner mb-4" style="--clase-color: <?= escapar($clase['color']) ?>">
    <div>
        <p class="clase-banner-institucion"><i class="bi bi-building me-1" aria-hidden="true"></i><?= escapar($clase['institucion'] ?? 'Sin institución') ?></p>
        <?php if (trim((string)$clase['descripcion']) !== ''): ?><p class="clase-banner-descripcion"><?= nl2br(escapar($clase['descripcion'])) ?></p><?php endif; ?>
    </div>
    <a class="btn btn-light btn-sm" href="clase_form.php?id=<?= $idClase ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar clase</a>
</section>

<div class="row g-4">
    <div class="col-lg-5">
        <section class="card-ui codigo-card" aria-labelledby="codigo-titulo">
            <h2 class="card-title-ui mb-3" id="codigo-titulo"><i class="bi bi-key" aria-hidden="true"></i>Código para unirse</h2>
            <?php if ($codigoVisible): ?>
                <p class="codigo-grande" id="codigo-clase"><?= escapar($clase['codigo_union']) ?></p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="button" data-copiar="<?= escapar($clase['codigo_union']) ?>">
                        <i class="bi bi-clipboard me-1" aria-hidden="true"></i><span>Copiar código</span>
                    </button>
                    <label class="form-label small mb-0 mt-2" for="enlace-union">Enlace para unirse</label>
                    <div class="input-group">
                        <input class="form-control form-control-sm" id="enlace-union" value="<?= escapar($enlaceUnion) ?>" readonly>
                        <button class="btn btn-outline-primary btn-sm" type="button" data-copiar="<?= escapar($enlaceUnion) ?>">
                            <i class="bi bi-link-45deg me-1" aria-hidden="true"></i><span>Copiar enlace</span>
                        </button>
                    </div>
                </div>
                <p class="visually-hidden" id="copiado-aviso" role="status" aria-live="polite"></p>
            <?php else: ?>
                <p class="codigo-grande codigo-apagado"><?= $clase['codigo_union'] !== null ? escapar($clase['codigo_union']) : '— — —' ?></p>
                <p class="text-secondary small text-center">El código está desactivado: nadie puede unirse a esta clase.</p>
            <?php endif; ?>

            <hr>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="clase.php" data-confirmar="Se generará un código nuevo y el actual dejará de funcionar. ¿Continuar?">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= $idClase ?>">
                    <button class="btn btn-sm btn-outline-primary" name="accion" value="regenerar" type="submit"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Generar código nuevo</button>
                </form>
                <form method="post" action="clase.php">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= $idClase ?>">
                    <?php if ($codigoVisible): ?>
                        <button class="btn btn-sm btn-outline-warning" name="accion" value="desactivar" type="submit"><i class="bi bi-pause-circle me-1" aria-hidden="true"></i>Desactivar código</button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-success" name="accion" value="activar" type="submit"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Activar código</button>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <section class="card-ui mt-4" aria-labelledby="evals-titulo">
            <div class="section-header">
                <h2 class="card-title-ui" id="evals-titulo"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Evaluaciones</h2>
                <a class="btn btn-sm btn-outline-primary" href="evaluacion_form.php"><i class="bi bi-plus-lg" aria-hidden="true"></i><span class="visually-hidden">Nueva evaluación</span></a>
            </div>
            <?php if (!$evaluaciones): ?>
                <p class="text-secondary small mb-0">Ninguna evaluación asignada. Al crear o editar una evaluación, marca esta clase.</p>
            <?php else: ?>
                <ul class="lista-simple">
                    <?php foreach ($evaluaciones as $evaluacion): ?>
                        <li>
                            <a href="evaluacion_form.php?id=<?= (int)$evaluacion['id'] ?>"><?= escapar($evaluacion['titulo']) ?></a>
                            <span class="badge-estado <?= (int)$evaluacion['publicada'] === 1 ? 'badge-publicada' : 'badge-borrador' ?>"><?= (int)$evaluacion['publicada'] === 1 ? 'Publicada' : 'Borrador' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="card-ui" aria-labelledby="estudiantes-titulo">
            <div class="section-header">
                <h2 class="card-title-ui" id="estudiantes-titulo"><i class="bi bi-people" aria-hidden="true"></i>Estudiantes</h2>
                <span class="text-secondary small"><?= count($estudiantes) ?> unido<?= count($estudiantes) === 1 ? '' : 's' ?></span>
            </div>
            <?php if (!$estudiantes): ?>
                <div class="empty-state py-4">
                    <span class="empty-icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                    <h3 class="h6">Todavía no se ha unido nadie</h3>
                    <p class="mb-0">Comparte el código o el enlace con tus estudiantes.</p>
                </div>
            <?php else: ?>
                <ul class="lista-estudiantes">
                    <?php foreach ($estudiantes as $estudiante): ?>
                        <li>
                            <span class="avatar avatar-sm" aria-hidden="true"><?= escapar(iniciales($estudiante['nombre'])) ?></span>
                            <span class="miembro-info">
                                <strong><?= escapar($estudiante['nombre']) ?><?= (int)$estudiante['activo'] === 1 ? '' : ' (desactivado)' ?></strong>
                                <small><?= escapar($estudiante['correo']) ?> · desde <?= escapar(formatoFecha($estudiante['unido_en'])) ?></small>
                            </span>
                            <form method="post" action="clase.php" data-confirmar="¿Sacar a <?= escapar($estudiante['nombre']) ?> de la clase? Dejará de ver sus evaluaciones.">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="id" value="<?= $idClase ?>">
                                <input type="hidden" name="estudiante_id" value="<?= (int)$estudiante['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" name="accion" value="sacar" type="submit" aria-label="Sacar a <?= escapar($estudiante['nombre']) ?>">
                                    <i class="bi bi-person-dash" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Sacar</span>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
