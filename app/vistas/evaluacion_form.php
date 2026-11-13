<?php
// Vista del formulario de evaluación. Variables: $valores, $errores, $editando, $publicada, $evaluacionId.
$campo = static fn(string $nombre): string => isset($errores[$nombre]) ? ' is-invalid' : '';
$error = static fn(string $nombre): string => isset($errores[$nombre])
    ? '<div class="invalid-feedback d-block" id="error-' . escapar($nombre) . '">' . escapar($errores[$nombre]) . '</div>'
    : '';
$describe = static function (string $nombre, string $extra = '') use ($errores): string {
    $ids = trim($extra . (isset($errores[$nombre]) ? ' error-' . $nombre : ''));
    return $ids === '' ? '' : ' aria-describedby="' . escapar($ids) . '"';
};
?>
<p><a class="btn btn-sm btn-outline-primary" href="evaluaciones.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a Mis evaluaciones</a></p>

<?php if ($errores): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Revisa los datos del formulario:</strong>
        <ul class="mb-0">
            <?php foreach ($errores as $mensajeError): ?><li><?= escapar($mensajeError) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($publicada): ?>
    <div class="alert alert-warning" role="status">Esta evaluación está <strong>publicada</strong>: los cambios serán visibles para los estudiantes al guardar.</div>
<?php endif; ?>

<form class="card-ui evaluacion-form" method="post" action="evaluacion_form.php" novalidate>
    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$evaluacionId ?>"><?php endif; ?>

    <div class="mb-3">
        <label class="form-label" for="titulo">Título <span aria-hidden="true">*</span></label>
        <input class="form-control<?= $campo('titulo') ?>" id="titulo" name="titulo" maxlength="200" required
               value="<?= escapar($valores['titulo']) ?>"<?= $describe('titulo') ?>>
        <?= $error('titulo') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea class="form-control<?= $campo('descripcion') ?>" id="descripcion" name="descripcion" rows="4"
                  maxlength="5000"<?= $describe('descripcion') ?>><?= escapar($valores['descripcion']) ?></textarea>
        <?= $error('descripcion') ?>
    </div>

    <div class="row g-3">
        <div class="col-sm-6">
            <label class="form-label" for="apertura">Fecha de apertura <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('apertura') ?>" id="apertura" name="apertura" type="datetime-local" required
                   value="<?= escapar($valores['apertura']) ?>"<?= $describe('apertura') ?>>
            <?= $error('apertura') ?>
        </div>
        <div class="col-sm-6">
            <label class="form-label" for="cierre">Fecha de cierre <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('cierre') ?>" id="cierre" name="cierre" type="datetime-local" required
                   value="<?= escapar($valores['cierre']) ?>"<?= $describe('cierre') ?>>
            <?= $error('cierre') ?>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="tiempo_minutos">Tiempo límite (minutos) <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('tiempo_minutos') ?>" id="tiempo_minutos" name="tiempo_minutos" type="number"
                   min="1" max="<?= TIEMPO_MAXIMO_MINUTOS ?>" step="1" required
                   value="<?= escapar($valores['tiempo_minutos']) ?>"<?= $describe('tiempo_minutos') ?>>
            <?= $error('tiempo_minutos') ?>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="intentos_permitidos">Intentos permitidos <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('intentos_permitidos') ?>" id="intentos_permitidos" name="intentos_permitidos"
                   type="number" min="1" max="<?= INTENTOS_MAXIMOS ?>" step="1" required
                   value="<?= escapar($valores['intentos_permitidos']) ?>"<?= $describe('intentos_permitidos') ?>>
            <?= $error('intentos_permitidos') ?>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="nota_minima">Nota mínima para aprobar <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('nota_minima') ?>" id="nota_minima" name="nota_minima" type="number"
                   min="0" max="<?= NOTA_MAXIMA ?>" step="0.01" required
                   value="<?= escapar($valores['nota_minima']) ?>"<?= $describe('nota_minima', 'ayuda-nota') ?>>
            <div class="form-text" id="ayuda-nota">Escala de 0 a <?= NOTA_MAXIMA ?>.</div>
            <?= $error('nota_minima') ?>
        </div>
    </div>

    <fieldset class="mt-4 asignacion"<?= $describe('clases') ?>>
        <legend class="form-label fs-6 fw-semibold mb-1"><i class="bi bi-easel me-1" aria-hidden="true"></i>¿En qué clases?</legend>
        <p class="form-text mt-0 mb-3">Los estudiantes de las clases marcadas la verán cuando esté <strong>publicada</strong>.</p>
        <?php if ($clasesPropias): ?>
            <div class="grupos-lista">
                <?php foreach ($clasesPropias as $claseOpcion): ?>
                    <?php $cid = (int)$claseOpcion['id']; ?>
                    <div class="form-check grupo-opcion" style="--clase-color: <?= escapar($claseOpcion['color']) ?>">
                        <input class="form-check-input" type="checkbox" id="clase-<?= $cid ?>" name="clases[]" value="<?= $cid ?>"<?= in_array($cid, $valores['clases'], true) ? ' checked' : '' ?>>
                        <label class="form-check-label" for="clase-<?= $cid ?>">
                            <span class="punto-color" aria-hidden="true"></span><?= escapar($claseOpcion['nombre']) ?>
                            <span class="d-block text-secondary small"><?= (int)$claseOpcion['estudiantes'] ?> estudiante<?= (int)$claseOpcion['estudiantes'] === 1 ? '' : 's' ?><?= $claseOpcion['institucion'] ? ' · ' . escapar($claseOpcion['institucion']) : '' ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small text-secondary mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Aún no tienes clases. <a href="clase_form.php">Crea una clase</a> y vuelve para asignarla.</p>
        <?php endif; ?>
        <?= $error('clases') ?>
    </fieldset>

    <fieldset class="mt-4">
        <legend class="form-label fs-6 fw-semibold">Opciones</legend>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="orden_aleatorio" name="orden_aleatorio" value="1"<?= $valores['orden_aleatorio'] ? ' checked' : '' ?>>
            <label class="form-check-label" for="orden_aleatorio">Mostrar las preguntas en orden aleatorio</label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="mostrar_retroalimentacion" name="mostrar_retroalimentacion" value="1"<?= $valores['mostrar_retroalimentacion'] ? ' checked' : '' ?>>
            <label class="form-check-label" for="mostrar_retroalimentacion">Mostrar retroalimentación al estudiante</label>
        </div>
    </fieldset>

    <p class="form-text mt-3 mb-0"><span aria-hidden="true">*</span> Campo obligatorio.<?= $editando ? '' : ' La evaluación se guardará como borrador.' ?></p>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear evaluación' ?></button>
        <a class="btn btn-outline-secondary" href="evaluaciones.php">Cancelar</a>
    </div>
</form>
