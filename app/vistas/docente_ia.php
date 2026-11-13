<?php
// Formulario "Generar con IA".
// Variables: $valores, $errores, $evaluaciones, $evaluacionesEditables, $errorConfiguracion, $avisoResumen, $mensaje.
$error = static fn(string $nombre): string => isset($errores[$nombre])
    ? '<div class="invalid-feedback d-block" id="error-' . escapar($nombre) . '">' . escapar($errores[$nombre]) . '</div>' : '';
$invalido = static fn(string $nombre): string => isset($errores[$nombre]) ? ' is-invalid' : '';
$describe = static fn(string $nombre, string $extra = ''): string => ($ids = trim($extra . (isset($errores[$nombre]) ? ' error-' . $nombre : ''))) !== ''
    ? ' aria-describedby="' . escapar($ids) . '"' : '';
$pestanas = ['texto' => ['Pegar texto', 'bi-textarea-t'], 'archivo' => ['Subir archivo', 'bi-file-earmark-arrow-up'], 'tema' => ['Desde un tema', 'bi-lightbulb']];
$activa = isset($pestanas[$valores['fuente']]) ? $valores['fuente'] : 'texto';
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<?php if ($errorConfiguracion !== null): ?>
    <div class="alert alert-warning d-flex gap-2" role="alert">
        <i class="bi bi-key-fill mt-1" aria-hidden="true"></i>
        <div><strong>La IA aún no está configurada.</strong> <?= escapar($errorConfiguracion) ?></div>
    </div>
<?php endif; ?>
<?php if (isset($errores['general'])): ?>
    <div class="alert alert-danger d-flex gap-2" role="alert"><i class="bi bi-exclamation-octagon-fill mt-1" aria-hidden="true"></i><span><?= escapar($errores['general']) ?></span></div>
<?php endif; ?>
<?php if ($avisoResumen): ?>
    <div class="alert alert-info d-flex gap-2" role="status">
        <i class="bi bi-pencil-square mt-1" aria-hidden="true"></i>
        <span><strong>Resumen listo.</strong> La IA lo escribió sin una fuente: léelo, corrige lo que haga falta y luego pulsa «Generar preguntas».</span>
    </div>
<?php endif; ?>

<form class="ia-form" id="form-ia" method="post" action="ia.php" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
    <input type="hidden" name="fuente" id="fuente" value="<?= escapar($activa) ?>">
    <input type="hidden" name="desde_tema" value="<?= escapar($valores['desde_tema']) ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= IA_MAX_ARCHIVO ?>">

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card-ui" aria-labelledby="fuente-titulo">
                <h2 class="card-title-ui mb-3" id="fuente-titulo"><i class="bi bi-journal-text" aria-hidden="true"></i>¿De dónde salen las preguntas?</h2>
                <div class="ia-tabs" role="tablist" aria-label="Fuente de las preguntas">
                    <?php foreach ($pestanas as $clave => [$etiqueta, $icono]): ?>
                        <button class="ia-tab<?= $activa === $clave ? ' active' : '' ?>" type="button" role="tab" id="tab-<?= $clave ?>"
                                aria-controls="panel-<?= $clave ?>" aria-selected="<?= $activa === $clave ? 'true' : 'false' ?>" tabindex="<?= $activa === $clave ? '0' : '-1' ?>" data-fuente="<?= $clave ?>">
                            <i class="bi <?= $icono ?>" aria-hidden="true"></i><span><?= escapar($etiqueta) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="ia-panel" role="tabpanel" id="panel-texto" aria-labelledby="tab-texto"<?= $activa === 'texto' ? '' : ' hidden' ?>>
                    <?php if ($valores['desde_tema'] === '1'): ?>
                        <p class="ia-aviso-tema"><i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>Este texto lo escribió la IA a partir de un tema. Revísalo antes de usarlo.</p>
                    <?php endif; ?>
                    <label class="form-label" for="texto">Texto base</label>
                    <textarea class="form-control<?= $invalido('texto') ?>" id="texto" name="texto" rows="12"
                              placeholder="Pega aquí la lectura, apuntes o capítulo del que quieres sacar preguntas."<?= $describe('texto', 'ayuda-texto') ?>><?= escapar($valores['texto']) ?></textarea>
                    <div class="d-flex justify-content-between form-text" id="ayuda-texto">
                        <span>Mínimo 100 caracteres. Si supera <?= number_format(IA_MAX_CARACTERES, 0, ',', '.') ?>, se recorta y te avisamos.</span>
                        <span id="contador-texto" aria-live="polite"></span>
                    </div>
                    <?= $error('texto') ?>
                </div>

                <div class="ia-panel" role="tabpanel" id="panel-archivo" aria-labelledby="tab-archivo"<?= $activa === 'archivo' ? '' : ' hidden' ?>>
                    <label class="ia-dropzone<?= isset($errores['archivo']) ? ' is-invalid' : '' ?>" for="archivo">
                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                        <strong id="archivo-nombre">Elige un archivo PDF o TXT</strong>
                        <span class="small text-secondary">Máximo 5 MB. El PDF debe tener texto seleccionable (no escaneado).</span>
                        <input class="visually-hidden-focusable-input" type="file" id="archivo" name="archivo" accept=".pdf,.txt,application/pdf,text/plain"<?= $describe('archivo') ?>>
                    </label>
                    <?php if (isset($errores['archivo'])): ?><p class="small text-secondary mt-2 mb-0">Por seguridad, vuelve a seleccionar el archivo.</p><?php endif; ?>
                    <?= $error('archivo') ?>
                </div>

                <div class="ia-panel" role="tabpanel" id="panel-tema" aria-labelledby="tab-tema"<?= $activa === 'tema' ? '' : ' hidden' ?>>
                    <div class="ia-aviso-tema mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
                        <strong>Revisa bien cada respuesta.</strong> Sin un texto base la IA puede equivocarse o inventar datos (fechas, cifras, nombres).
                    </div>
                    <label class="form-label" for="tema">Tema</label>
                    <input class="form-control<?= $invalido('tema') ?>" id="tema" name="tema" maxlength="200"
                           placeholder="Ej. La Revolución Francesa · Fracciones" value="<?= escapar($valores['tema']) ?>"<?= $describe('tema') ?>>
                    <?= $error('tema') ?>
                    <div class="row g-3 mt-0">
                        <div class="col-md-5">
                            <label class="form-label" for="nivel">Nivel o grado</label>
                            <input class="form-control" id="nivel" name="nivel" maxlength="100" placeholder="Ej. Grado sexto · Técnico SENA" value="<?= escapar($valores['nivel']) ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="subtemas">Subtemas a cubrir <span class="text-secondary small fw-normal">(opcional)</span></label>
                            <input class="form-control" id="subtemas" name="subtemas" maxlength="500" placeholder="Ej. causas, Toma de la Bastilla, consecuencias" value="<?= escapar($valores['subtemas']) ?>">
                        </div>
                    </div>
                    <div class="ia-resumen mt-3">
                        <div>
                            <strong>¿Prefieres partir de un texto?</strong>
                            <p class="small text-secondary mb-0">La IA escribe un resumen corto del tema; tú lo editas y luego generas las preguntas desde ese texto.</p>
                        </div>
                        <button class="btn btn-outline-primary" type="submit" name="accion" value="resumen" data-carga="La IA está escribiendo el resumen del tema..."<?= $errorConfiguracion !== null ? ' disabled' : '' ?>>
                            <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Generar resumen del tema
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card-ui" aria-labelledby="opciones-titulo">
                <h2 class="card-title-ui mb-3" id="opciones-titulo"><i class="bi bi-sliders" aria-hidden="true"></i>Opciones</h2>

                <label class="form-label" for="evaluacion">Evaluación de destino</label>
                <select class="form-select<?= $invalido('evaluacion') ?>" id="evaluacion" name="evaluacion"<?= $describe('evaluacion') ?>>
                    <option value="">Elige una evaluación</option>
                    <?php foreach ($evaluaciones as $evaluacion): ?>
                        <?php $eid = (int)$evaluacion['id']; $editable = isset($evaluacionesEditables[$eid]); ?>
                        <option value="<?= $eid ?>"<?= $valores['evaluacion'] === (string)$eid && $editable ? ' selected' : '' ?><?= $editable ? '' : ' disabled' ?>>
                            <?= escapar($evaluacion['titulo']) ?><?= $editable ? '' : ' (ya tiene intentos)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $error('evaluacion') ?>
                <?php if (!$evaluaciones): ?><p class="small text-secondary mt-1 mb-0">Primero <a href="evaluacion_form.php">crea una evaluación</a>.</p><?php endif; ?>

                <div class="row g-3 mt-0">
                    <div class="col-6">
                        <label class="form-label" for="cantidad">Cantidad</label>
                        <input class="form-control<?= $invalido('cantidad') ?>" id="cantidad" name="cantidad" type="number" min="1" max="<?= IA_MAX_PREGUNTAS ?>" value="<?= escapar($valores['cantidad']) ?>"<?= $describe('cantidad') ?>>
                        <?= $error('cantidad') ?>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="puntaje">Puntaje c/u</label>
                        <input class="form-control<?= $invalido('puntaje') ?>" id="puntaje" name="puntaje" type="number" min="0.01" max="1000" step="0.01" value="<?= escapar($valores['puntaje']) ?>"<?= $describe('puntaje') ?>>
                        <?= $error('puntaje') ?>
                    </div>
                </div>

                <label class="form-label mt-3" for="tipo">Tipo de pregunta</label>
                <select class="form-select<?= $invalido('tipo') ?>" id="tipo" name="tipo"<?= $describe('tipo') ?>>
                    <?php foreach (IA_TIPOS as $clave => $etiqueta): ?>
                        <option value="<?= escapar($clave) ?>"<?= $valores['tipo'] === $clave ? ' selected' : '' ?>><?= escapar($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $error('tipo') ?>

                <fieldset class="mt-3">
                    <legend class="form-label fs-6">Dificultad</legend>
                    <div class="ia-segmentado">
                        <?php foreach (IA_DIFICULTADES as $clave => $etiqueta): ?>
                            <input type="radio" class="btn-check" name="dificultad" id="dif-<?= $clave ?>" value="<?= $clave ?>"<?= $valores['dificultad'] === $clave ? ' checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="dif-<?= $clave ?>"><?= escapar($etiqueta) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?= $error('dificultad') ?>
                </fieldset>

                <label class="form-label mt-3" for="instrucciones">Instrucciones extra <span class="text-secondary small fw-normal">(opcional)</span></label>
                <textarea class="form-control" id="instrucciones" name="instrucciones" rows="2" maxlength="500" placeholder="Ej. Enfócate en las fechas y personajes."><?= escapar($valores['instrucciones']) ?></textarea>

                <button class="btn btn-primary btn-lg w-100 mt-4" type="submit" name="accion" value="generar" data-carga="La IA está creando tus preguntas..."<?= $errorConfiguracion !== null ? ' disabled' : '' ?>>
                    <i class="bi bi-stars me-1" aria-hidden="true"></i>Generar preguntas
                </button>
                <p class="small text-secondary text-center mt-2 mb-0">Nada se guarda hasta que revises y apruebes las preguntas.</p>
            </section>
        </div>
    </div>
</form>

<div class="ia-carga" id="ia-carga" hidden role="alert" aria-live="assertive">
    <div class="ia-carga-caja">
        <div class="ia-orbita" aria-hidden="true"><i class="bi bi-stars"></i></div>
        <p class="ia-carga-mensaje" id="ia-carga-mensaje">La IA está creando tus preguntas...</p>
        <p class="small text-secondary mb-0">Puede tardar hasta un minuto. No cierres esta página.</p>
    </div>
</div>
