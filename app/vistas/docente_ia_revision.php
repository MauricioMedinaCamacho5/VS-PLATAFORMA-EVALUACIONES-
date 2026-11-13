<?php
// Revisión de preguntas generadas por IA.
// Variables: $borrador, $borradorId, $evaluacion, $conteo, $editando, $erroresEdicion, $mensaje.
$csrf = escapar(csrfToken());
$bid = escapar((string)$borradorId);
$etiquetaEstado = ['pendiente' => ['Por revisar', 'badge-proxima'], 'aprobada' => ['Aprobada', 'badge-publicada'], 'descartada' => ['Descartada', 'badge-borrador']];
$formatoPuntaje = static fn(float $n): string => rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',');
$origenes = ['texto' => 'texto pegado', 'archivo' => 'archivo', 'tema' => 'un tema'];
$campoOculto = static fn(): string => '<input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="borrador" value="' . $bid . '">';
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<?php foreach ($borrador['avisos'] as $aviso): ?>
    <div class="alert alert-info d-flex gap-2 py-2" role="status"><i class="bi bi-info-circle-fill mt-1" aria-hidden="true"></i><span><?= escapar($aviso) ?></span></div>
<?php endforeach; ?>
<?php if ($borrador['sin_texto_base']): ?>
    <div class="ia-aviso-tema mb-3" role="note">
        <i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>
        <strong>Generadas sin un texto base verificado.</strong> La IA puede equivocarse: comprueba cada respuesta correcta antes de aprobarla.
    </div>
<?php endif; ?>

<section class="card-ui ia-barra mb-4" aria-label="Resumen y acciones">
    <div class="ia-contadores">
        <span><strong><?= $conteo['aprobada'] ?></strong> aprobadas</span>
        <span><strong><?= $conteo['pendiente'] ?></strong> por revisar</span>
        <span><strong><?= $conteo['descartada'] ?></strong> descartadas</span>
        <span class="text-secondary small">Origen: <?= escapar($origenes[$borrador['origen']] ?? $borrador['origen']) ?><?= $borrador['fuente']['tipo'] === 'tema' ? ' («' . escapar($borrador['fuente']['tema']) . '»)' : '' ?></span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="ia_revision.php">
            <?= $campoOculto() ?>
            <button class="btn btn-success" name="accion" value="aprobar_todas" type="submit"<?= $conteo['pendiente'] === 0 ? ' disabled' : '' ?>><i class="bi bi-check2-all me-1" aria-hidden="true"></i>Aprobar todas</button>
        </form>
        <form method="post" action="ia_revision.php" class="d-flex gap-2 align-items-center">
            <?= $campoOculto() ?>
            <label class="visually-hidden" for="mas-cantidad">Cuántas preguntas más</label>
            <input class="form-control ia-mas-cantidad" id="mas-cantidad" name="cantidad" type="number" min="1" max="<?= IA_MAX_PREGUNTAS ?>" value="5">
            <button class="btn btn-outline-primary text-nowrap" name="accion" value="generar_mas" type="submit" data-carga="La IA está creando más preguntas..."><i class="bi bi-stars me-1" aria-hidden="true"></i>Generar más</button>
        </form>
    </div>
</section>

<div class="ia-lista">
    <?php foreach ($borrador['preguntas'] as $numero => $p): ?>
        <?php [$textoEstado, $claseEstado] = $etiquetaEstado[$p['estado']]; ?>
        <article class="card-ui ia-pregunta ia-<?= escapar($p['estado']) ?>" id="pregunta-<?= (int)$numero ?>" aria-labelledby="enunciado-<?= (int)$numero ?>">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge-estado <?= $claseEstado ?>"><?= $textoEstado ?></span>
                <span class="badge-estado badge-borrador"><?= escapar(TIPOS_PREGUNTA[$p['tipo']]) ?></span>
                <span class="badge-estado ia-dificultad-<?= escapar($p['dificultad']) ?>"><?= escapar(IA_DIFICULTADES[$p['dificultad']] ?? $p['dificultad']) ?></span>
                <span class="badge-estado badge-borrador"><?= escapar($formatoPuntaje((float)$p['puntaje'])) ?> pt</span>
                <?php if (!empty($p['editada'])): ?><span class="badge-estado badge-borrador"><i class="bi bi-pencil" aria-hidden="true"></i>Editada</span><?php endif; ?>
            </div>

            <?php if ($editando === $numero): ?>
                <?php $v = $p; ?>
                <form method="post" action="ia_revision.php" class="ia-editar" novalidate>
                    <?= $campoOculto() ?>
                    <input type="hidden" name="pregunta" value="<?= (int)$numero ?>">
                    <?php if (isset($erroresEdicion['general'])): ?><div class="alert alert-danger py-2" role="alert"><?= escapar($erroresEdicion['general']) ?></div><?php endif; ?>
                    <label class="form-label" for="ed-enunciado">Enunciado</label>
                    <textarea class="form-control" id="ed-enunciado" name="enunciado" rows="3" maxlength="2000"><?= escapar($v['enunciado']) ?></textarea>

                    <?php if ($p['tipo'] === 'seleccion_multiple'): ?>
                        <fieldset class="mt-3">
                            <legend class="form-label fs-6">Opciones <span class="text-secondary small fw-normal">(marca la correcta)</span></legend>
                            <?php for ($i = 0; $i < max(4, count($v['opciones'])); $i++): ?>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correcta" value="<?= $i ?>" aria-label="Opción <?= $i + 1 ?> es la correcta"<?= $v['correcta'] === $i ? ' checked' : '' ?>></span>
                                    <input class="form-control" name="opciones[]" maxlength="500" value="<?= escapar($v['opciones'][$i] ?? '') ?>" aria-label="Texto de la opción <?= $i + 1 ?>">
                                </div>
                            <?php endfor; ?>
                        </fieldset>
                    <?php elseif ($p['tipo'] === 'verdadero_falso'): ?>
                        <fieldset class="mt-3">
                            <legend class="form-label fs-6">La afirmación es…</legend>
                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="ed-v" name="correcta" value="0"<?= $v['correcta'] === 0 ? ' checked' : '' ?>><label class="form-check-label" for="ed-v">Verdadera</label></div>
                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="ed-f" name="correcta" value="1"<?= $v['correcta'] === 1 ? ' checked' : '' ?>><label class="form-check-label" for="ed-f">Falsa</label></div>
                        </fieldset>
                    <?php else: ?>
                        <label class="form-label mt-3" for="ed-rc">Respuesta esperada <span class="text-secondary small fw-normal">(1 a 6 palabras)</span></label>
                        <input class="form-control" id="ed-rc" name="respuesta_corta" maxlength="100" value="<?= escapar($v['respuesta_corta']) ?>">
                    <?php endif; ?>

                    <div class="row g-3 mt-0">
                        <div class="col-sm-4">
                            <label class="form-label" for="ed-puntaje">Puntaje</label>
                            <input class="form-control<?= isset($erroresEdicion['puntaje']) ? ' is-invalid' : '' ?>" id="ed-puntaje" name="puntaje" type="number" min="0.01" max="1000" step="0.01" value="<?= escapar((string)$v['puntaje']) ?>">
                            <?php if (isset($erroresEdicion['puntaje'])): ?><div class="invalid-feedback d-block"><?= escapar($erroresEdicion['puntaje']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-sm-8">
                            <label class="form-label" for="ed-dificultad">Dificultad</label>
                            <select class="form-select" id="ed-dificultad" name="dificultad">
                                <?php foreach (IA_DIFICULTADES as $clave => $etiqueta): ?><option value="<?= $clave ?>"<?= $v['dificultad'] === $clave ? ' selected' : '' ?>><?= escapar($etiqueta) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <label class="form-label mt-3" for="ed-retro">Retroalimentación</label>
                    <textarea class="form-control" id="ed-retro" name="retroalimentacion" rows="2" maxlength="1000"><?= escapar($v['retroalimentacion']) ?></textarea>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-primary" name="accion" value="editar" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Guardar y aprobar</button>
                        <a class="btn btn-outline-secondary" href="ia_revision.php?borrador=<?= $bid ?>#pregunta-<?= (int)$numero ?>">Cancelar</a>
                    </div>
                </form>
            <?php else: ?>
                <p class="pregunta-enunciado" id="enunciado-<?= (int)$numero ?>"><?= escapar($p['enunciado']) ?></p>
                <?php if ($p['tipo'] === 'respuesta_corta'): ?>
                    <p class="ia-correcta mb-2"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Respuesta esperada: <strong><?= escapar($p['respuesta_corta']) ?></strong></p>
                <?php else: ?>
                    <ul class="ia-opciones">
                        <?php foreach ($p['opciones'] as $indice => $opcion): ?>
                            <li class="<?= $indice === $p['correcta'] ? 'ia-correcta' : '' ?>">
                                <i class="bi <?= $indice === $p['correcta'] ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                                <span><?= escapar($opcion) ?></span>
                                <?php if ($indice === $p['correcta']): ?><span class="ia-correcta-etiqueta">Correcta</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($p['retroalimentacion'] !== ''): ?>
                    <p class="retro mb-3"><i class="bi bi-lightbulb me-1" aria-hidden="true"></i><?= escapar($p['retroalimentacion']) ?></p>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" action="ia_revision.php"><?= $campoOculto() ?><input type="hidden" name="pregunta" value="<?= (int)$numero ?>">
                        <?php if ($p['estado'] === 'pendiente'): ?>
                            <button class="btn btn-sm btn-success" name="accion" value="aprobar" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aprobar</button>
                            <button class="btn btn-sm btn-outline-danger" name="accion" value="descartar" type="submit"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Descartar</button>
                        <?php elseif ($p['estado'] === 'aprobada'): ?>
                            <button class="btn btn-sm btn-outline-secondary" name="accion" value="restaurar" type="submit"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Quitar aprobación</button>
                            <button class="btn btn-sm btn-outline-danger" name="accion" value="descartar" type="submit"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Descartar</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" name="accion" value="restaurar" type="submit"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Recuperar</button>
                        <?php endif; ?>
                    </form>
                    <?php if ($p['estado'] !== 'descartada'): ?>
                        <a class="btn btn-sm btn-outline-primary" href="ia_revision.php?borrador=<?= $bid ?>&amp;editar=<?= (int)$numero ?>#pregunta-<?= (int)$numero ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>

<section class="card-ui ia-guardar mt-4" aria-labelledby="guardar-titulo">
    <h2 class="card-title-ui mb-2" id="guardar-titulo"><i class="bi bi-save" aria-hidden="true"></i>Guardar en «<?= escapar($evaluacion['titulo']) ?>»</h2>
    <p class="small text-secondary">Solo se guardan las <strong><?= $conteo['aprobada'] ?></strong> preguntas aprobadas. Las que estén por revisar o descartadas se pierden al guardar.</p>
    <form method="post" action="ia_revision.php" class="d-flex flex-wrap align-items-center gap-3">
        <?= $campoOculto() ?>
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="al-banco" name="al_banco" value="1">
            <label class="form-check-label" for="al-banco">Guardarlas también en mi banco de preguntas</label>
        </div>
        <button class="btn btn-primary ms-auto" name="accion" value="guardar" type="submit"<?= $conteo['aprobada'] === 0 ? ' disabled' : '' ?>>
            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Guardar <?= $conteo['aprobada'] ?> aprobada<?= $conteo['aprobada'] === 1 ? '' : 's' ?>
        </button>
        <button class="btn btn-outline-danger" name="accion" value="cancelar" type="submit" data-confirmar="¿Descartar todo el borrador? No se guardará ninguna pregunta.">Descartar borrador</button>
    </form>
</section>

<div class="ia-carga" id="ia-carga" hidden role="alert" aria-live="assertive">
    <div class="ia-carga-caja">
        <div class="ia-orbita" aria-hidden="true"><i class="bi bi-stars"></i></div>
        <p class="ia-carga-mensaje" id="ia-carga-mensaje">La IA está creando tus preguntas...</p>
        <p class="small text-secondary mb-0">Puede tardar hasta un minuto. No cierres esta página.</p>
    </div>
</div>
