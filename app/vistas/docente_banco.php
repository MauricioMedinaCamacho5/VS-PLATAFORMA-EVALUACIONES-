<?php
// Banco de preguntas del docente. Variables: $preguntas, $evaluacionesDisponibles, $valores, $errores, $mensaje.
$csrf = escapar(csrfToken());
$error = static fn(string $campo): string => isset($errores[$campo])
    ? '<div class="invalid-feedback d-block" id="error-' . escapar($campo) . '">' . escapar($errores[$campo]) . '</div>' : '';
$invalido = static fn(string $campo): string => isset($errores[$campo])
    ? ' is-invalid" aria-invalid="true" aria-describedby="error-' . escapar($campo) : '';
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<div class="row g-4">
    <div class="col-lg-7">
        <?php if (!$preguntas): ?>
            <section class="card-ui empty-state">
                <span class="empty-icon" aria-hidden="true"><i class="bi bi-journal-plus"></i></span>
                <h2>Tu banco está vacío</h2>
                <p>Guarda preguntas para volver a utilizarlas en tus evaluaciones.</p>
            </section>
        <?php else: ?>
            <section class="card-ui" aria-labelledby="banco-lista-titulo">
                <h2 class="card-title-ui mb-3" id="banco-lista-titulo"><i class="bi bi-collection" aria-hidden="true"></i><?= count($preguntas) ?> pregunta<?= count($preguntas) === 1 ? '' : 's' ?> guardada<?= count($preguntas) === 1 ? '' : 's' ?></h2>
                <ol class="lista-preguntas">
                    <?php foreach ($preguntas as $pregunta): ?>
                        <?php $opciones = json_decode((string)($pregunta['opciones_json'] ?? '[]'), true); ?>
                        <li class="pregunta-item">
                            <div class="d-flex justify-content-between gap-3">
                                <div class="min-w-0">
                                    <span class="badge-estado badge-borrador mb-1">
                                        <?= escapar(TIPOS_PREGUNTA[$pregunta['tipo']] ?? $pregunta['tipo']) ?>
                                        · <?= escapar(rtrim(rtrim((string)$pregunta['puntaje'], '0'), '.')) ?> pt
                                    </span>
                                    <p class="pregunta-enunciado"><?= nl2br(escapar($pregunta['enunciado'])) ?></p>
                                    <?php if ($pregunta['tipo'] === 'respuesta_corta'): ?>
                                        <p class="small mb-2">Respuesta esperada: <strong><?= escapar((string)$pregunta['respuesta_corta']) ?></strong></p>
                                    <?php elseif (is_array($opciones)): ?>
                                        <ul class="opciones-docente">
                                            <?php foreach ($opciones as $opcion): ?>
                                                <?php if (is_array($opcion) && is_string($opcion['texto'] ?? null)): ?>
                                                    <li class="<?= !empty($opcion['correcta']) ? 'es-correcta' : '' ?>">
                                                        <i class="bi <?= !empty($opcion['correcta']) ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                                                        <?= escapar($opcion['texto']) ?><?= !empty($opcion['correcta']) ? '<span class="visually-hidden"> (correcta)</span>' : '' ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <?php if ($pregunta['retroalimentacion'] !== null && $pregunta['retroalimentacion'] !== ''): ?>
                                        <p class="small text-secondary mb-2"><?= escapar($pregunta['retroalimentacion']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($evaluacionesDisponibles): ?>
                                        <form method="post" action="banco.php" class="d-flex flex-wrap gap-2 mt-3">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="accion" value="usar">
                                            <input type="hidden" name="pregunta" value="<?= (int)$pregunta['id'] ?>">
                                            <label class="visually-hidden" for="evaluacion-<?= (int)$pregunta['id'] ?>">Agregar a una evaluación</label>
                                            <select class="form-select form-select-sm w-auto" id="evaluacion-<?= (int)$pregunta['id'] ?>" name="evaluacion" required>
                                                <?php foreach ($evaluacionesDisponibles as $evaluacion): ?>
                                                    <option value="<?= (int)$evaluacion['id'] ?>"><?= escapar($evaluacion['titulo']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Agregar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <form method="post" action="banco.php" data-confirmar="¿Eliminar esta pregunta del banco?">
                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="pregunta" value="<?= (int)$pregunta['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Eliminar pregunta del banco"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <form class="card-ui" id="nueva" method="post" action="banco.php" novalidate>
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
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
                <input class="form-control<?= $invalido('respuesta_corta') ?>" id="respuesta_corta" name="respuesta_corta" maxlength="500" value="<?= escapar($valores['respuesta_corta']) ?>">
                <div class="form-text">Se compara sin importar mayúsculas, tildes ni espacios extra.</div>
                <?= $error('respuesta_corta') ?>
            </div>

            <label class="form-label mt-3" for="puntaje">Puntaje</label>
            <input class="form-control<?= $invalido('puntaje') ?>" id="puntaje" name="puntaje" type="number" min="0.01" max="1000" step="0.01" value="<?= escapar($valores['puntaje']) ?>">
            <?= $error('puntaje') ?>

            <label class="form-label mt-3" for="retroalimentacion">Retroalimentación <span class="text-secondary small fw-normal">(opcional)</span></label>
            <textarea class="form-control<?= $invalido('retroalimentacion') ?>" id="retroalimentacion" name="retroalimentacion" rows="2" maxlength="2000"><?= escapar($valores['retroalimentacion']) ?></textarea>
            <?= $error('retroalimentacion') ?>

            <button class="btn btn-primary w-100 mt-4" type="submit"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Guardar en el banco</button>
        </form>
    </div>
</div>
