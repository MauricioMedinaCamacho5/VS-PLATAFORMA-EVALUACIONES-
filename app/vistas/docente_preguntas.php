<?php
// Editor de preguntas (docente).
// Variables: $evaluacion, $preguntas, $opcionesPorPregunta, $valores, $errores, $bloqueada, $puntajeTotal, $mensaje.
$csrf = escapar(csrfToken());
$idEval = (int)$evaluacion['id'];
$error = static fn(string $nombre): string => isset($errores[$nombre])
    ? '<div class="invalid-feedback d-block" id="error-' . escapar($nombre) . '">' . escapar($errores[$nombre]) . '</div>' : '';
$invalido = static fn(string $nombre): string => isset($errores[$nombre]) ? ' is-invalid" aria-invalid="true" aria-describedby="error-' . escapar($nombre) : '';
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm btn-outline-primary" href="evaluaciones.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Mis evaluaciones</a>
    <a class="btn btn-sm btn-outline-primary" href="evaluacion_form.php?id=<?= $idEval ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar datos de la evaluación</a>
    <?php if (!$bloqueada): ?>
        <a class="btn btn-sm btn-primary ms-sm-auto" href="ia.php?evaluacion=<?= $idEval ?>"><i class="bi bi-stars me-1" aria-hidden="true"></i>Generar con IA</a>
    <?php endif; ?>
</div>

<?php if ($bloqueada): ?>
    <div class="alert alert-warning"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Ya hay estudiantes que presentaron esta evaluación: las preguntas quedan bloqueadas para no alterar sus notas.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="card-ui" aria-labelledby="lista-titulo">
            <div class="section-header">
                <h2 class="card-title-ui" id="lista-titulo"><i class="bi bi-list-check" aria-hidden="true"></i><?= count($preguntas) ?> pregunta<?= count($preguntas) === 1 ? '' : 's' ?></h2>
                <span class="text-secondary small">Puntaje total: <?= escapar(rtrim(rtrim(number_format($puntajeTotal, 2, ',', ''), '0'), ',')) ?></span>
            </div>
            <?php if (!$preguntas): ?>
                <div class="empty-state py-4">
                    <span class="empty-icon" aria-hidden="true"><i class="bi bi-question-lg"></i></span>
                    <h3 class="h6">Aún no hay preguntas</h3>
                    <p class="mb-0">Añade la primera con el formulario. La nota se calcula de 0 a <?= NOTA_MAXIMA ?> según los puntajes.</p>
                </div>
            <?php else: ?>
                <ol class="lista-preguntas">
                    <?php foreach ($preguntas as $pregunta): ?>
                        <li class="pregunta-item">
                            <div class="d-flex justify-content-between gap-3">
                                <div class="min-w-0">
                                    <span class="badge-estado badge-borrador mb-1"><?= escapar(TIPOS_PREGUNTA[$pregunta['tipo']] ?? $pregunta['tipo']) ?> · <?= escapar(rtrim(rtrim($pregunta['puntaje'], '0'), '.')) ?> pt</span>
                                    <p class="pregunta-enunciado"><?= nl2br(escapar($pregunta['enunciado'])) ?></p>
                                    <?php if ($pregunta['tipo'] === 'respuesta_corta'): ?>
                                        <p class="small mb-0"><i class="bi bi-check-circle-fill text-success me-1" aria-hidden="true"></i>Respuesta esperada: <strong><?= escapar((string)$pregunta['respuesta_corta']) ?></strong></p>
                                    <?php else: ?>
                                        <ul class="opciones-docente">
                                            <?php foreach ($opcionesPorPregunta[(int)$pregunta['id']] ?? [] as $opcion): ?>
                                                <li class="<?= (int)$opcion['correcta'] === 1 ? 'es-correcta' : '' ?>">
                                                    <i class="bi <?= (int)$opcion['correcta'] === 1 ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                                                    <?= escapar($opcion['texto']) ?><?= (int)$opcion['correcta'] === 1 ? '<span class="visually-hidden"> (correcta)</span>' : '' ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$bloqueada): ?>
                                    <form method="post" action="preguntas.php" data-confirmar="¿Eliminar esta pregunta?">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="evaluacion" value="<?= $idEval ?>">
                                        <input type="hidden" name="pregunta" value="<?= (int)$pregunta['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" name="accion" value="eliminar" type="submit" aria-label="Eliminar pregunta"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>

    <?php if (!$bloqueada): ?>
    <div class="col-lg-5">
        <form class="card-ui pregunta-form" id="nueva" method="post" action="preguntas.php" novalidate>
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="evaluacion" value="<?= $idEval ?>">
            <input type="hidden" name="accion" value="crear">
            <h2 class="card-title-ui mb-3"><i class="bi bi-plus-circle" aria-hidden="true"></i>Nueva pregunta</h2>

            <label class="form-label" for="tipo">Tipo</label>
            <select class="form-select<?= $invalido('tipo') ?>" id="tipo" name="tipo">
                <?php foreach (TIPOS_PREGUNTA as $clave => $nombreTipo): ?>
                    <option value="<?= escapar($clave) ?>"<?= $valores['tipo'] === $clave ? ' selected' : '' ?>><?= escapar($nombreTipo) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $error('tipo') ?>

            <label class="form-label mt-3" for="enunciado">Enunciado</label>
            <textarea class="form-control<?= $invalido('enunciado') ?>" id="enunciado" name="enunciado" rows="3" maxlength="5000" required><?= escapar($valores['enunciado']) ?></textarea>
            <?= $error('enunciado') ?>

            <fieldset class="mt-3 seccion-tipo" data-tipo="seleccion_multiple">
                <legend class="form-label fs-6">Opciones <span class="text-secondary small fw-normal">(marca la correcta)</span></legend>
                <?php foreach ($valores['opciones'] as $indice => $textoOpcion): ?>
                    <div class="input-group mb-2">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="radio" name="correcta" value="<?= (int)$indice ?>" aria-label="Opción <?= $indice + 1 ?> es la correcta"<?= (string)$indice === $valores['correcta'] ? ' checked' : '' ?>>
                        </span>
                        <input class="form-control" name="opciones[]" maxlength="1000" placeholder="Opción <?= $indice + 1 ?>" value="<?= escapar($textoOpcion) ?>" aria-label="Texto de la opción <?= $indice + 1 ?>">
                    </div>
                <?php endforeach; ?>
                <?= $error('opciones') ?>
            </fieldset>

            <fieldset class="mt-3 seccion-tipo" data-tipo="verdadero_falso">
                <legend class="form-label fs-6">La afirmación es…</legend>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="vf-v" name="correcta_vf" value="v"<?= $valores['correcta_vf'] === 'v' ? ' checked' : '' ?>>
                    <label class="form-check-label" for="vf-v">Verdadera</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="vf-f" name="correcta_vf" value="f"<?= $valores['correcta_vf'] === 'f' ? ' checked' : '' ?>>
                    <label class="form-check-label" for="vf-f">Falsa</label>
                </div>
                <?= $error('correcta_vf') ?>
            </fieldset>

            <div class="mt-3 seccion-tipo" data-tipo="respuesta_corta">
                <label class="form-label" for="respuesta_corta">Respuesta esperada</label>
                <input class="form-control<?= $invalido('respuesta_corta') ?>" id="respuesta_corta" name="respuesta_corta" maxlength="500" value="<?= escapar($valores['respuesta_corta']) ?>" aria-describedby="ayuda-rc">
                <div class="form-text" id="ayuda-rc">Se compara sin importar mayúsculas, tildes ni espacios extra.</div>
                <?= $error('respuesta_corta') ?>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-5">
                    <label class="form-label" for="puntaje">Puntaje</label>
                    <input class="form-control<?= $invalido('puntaje') ?>" id="puntaje" name="puntaje" type="number" min="0.01" max="1000" step="0.01" value="<?= escapar($valores['puntaje']) ?>">
                    <?= $error('puntaje') ?>
                </div>
            </div>

            <label class="form-label mt-3" for="retroalimentacion">Retroalimentación <span class="text-secondary small fw-normal">(opcional)</span></label>
            <textarea class="form-control<?= $invalido('retroalimentacion') ?>" id="retroalimentacion" name="retroalimentacion" rows="2" maxlength="2000" placeholder="Explicación que verá el estudiante al terminar, si la evaluación la muestra."><?= escapar($valores['retroalimentacion']) ?></textarea>
            <?= $error('retroalimentacion') ?>

            <button class="btn btn-primary w-100 mt-4" type="submit"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Añadir pregunta</button>
        </form>
    </div>
    <?php endif; ?>
</div>
