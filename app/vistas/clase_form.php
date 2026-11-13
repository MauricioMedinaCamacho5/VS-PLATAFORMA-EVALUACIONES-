<?php
// Formulario de clase. Variables: $valores, $errores, $editando, $claseId, $instituciones.
$campo = static fn(string $nombre): string => isset($errores[$nombre]) ? ' is-invalid' : '';
$error = static fn(string $nombre): string => isset($errores[$nombre])
    ? '<div class="invalid-feedback d-block" id="error-' . escapar($nombre) . '">' . escapar($errores[$nombre]) . '</div>'
    : '';
$describe = static fn(string $nombre): string => isset($errores[$nombre]) ? ' aria-describedby="error-' . escapar($nombre) . '" aria-invalid="true"' : '';
?>
<p><a class="btn btn-sm btn-outline-primary" href="<?= $editando ? 'clase.php?id=' . (int)$claseId : 'clases.php' ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver</a></p>

<?php if ($errores): ?>
    <div class="alert alert-danger" role="alert"><strong>Revisa los datos del formulario.</strong></div>
<?php endif; ?>

<form class="card-ui" method="post" action="clase_form.php" novalidate>
    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$claseId ?>"><?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="nombre">Nombre de la clase <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('nombre') ?>" id="nombre" name="nombre" maxlength="150" required
                   placeholder="Ej. Ficha 2845671" value="<?= escapar($valores['nombre']) ?>"<?= $describe('nombre') ?>>
            <?= $error('nombre') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="institucion">Institución o escuela</label>
            <input class="form-control<?= $campo('institucion') ?>" id="institucion" name="institucion" maxlength="150"
                   list="lista-instituciones" placeholder="Ej. SENA - Centro de Servicios Financieros"
                   value="<?= escapar($valores['institucion']) ?>"<?= $describe('institucion') ?>>
            <datalist id="lista-instituciones">
                <?php foreach ($instituciones as $nombreInstitucion): ?><option value="<?= escapar($nombreInstitucion) ?>"><?php endforeach; ?>
            </datalist>
            <?= $error('institucion') ?>
        </div>
    </div>

    <div class="mt-3">
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea class="form-control<?= $campo('descripcion') ?>" id="descripcion" name="descripcion" rows="3" maxlength="2000"
                  placeholder="Programa, jornada, horario…"<?= $describe('descripcion') ?>><?= escapar($valores['descripcion']) ?></textarea>
        <?= $error('descripcion') ?>
    </div>

    <fieldset class="mt-4">
        <legend class="form-label fs-6 fw-semibold">Color</legend>
        <div class="color-swatches">
            <?php foreach (COLORES_CLASE as $hex => $nombreColor): ?>
                <label class="color-swatch" style="--clase-color: <?= escapar($hex) ?>">
                    <input type="radio" name="color" value="<?= escapar($hex) ?>"<?= $valores['color'] === $hex ? ' checked' : '' ?>>
                    <span class="color-swatch-muestra" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <span class="visually-hidden"><?= escapar($nombreColor) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= $error('color') ?>
    </fieldset>

    <?php if (!$editando): ?>
        <p class="form-text mt-3 mb-0"><i class="bi bi-key me-1" aria-hidden="true"></i>Al crearla se genera un código de 6 caracteres para que tus estudiantes se unan.</p>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear clase' ?></button>
        <a class="btn btn-outline-secondary" href="<?= $editando ? 'clase.php?id=' . (int)$claseId : 'clases.php' ?>">Cancelar</a>
    </div>
</form>
