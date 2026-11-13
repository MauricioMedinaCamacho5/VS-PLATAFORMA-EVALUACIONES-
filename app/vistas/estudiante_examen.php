<?php
// Examen en curso. Variables: $intento, $preguntas.
// Solo se envían enunciados y textos de opciones: nunca cuál es la correcta.
$segundos = max(0, (int)$intento['segundos_restantes']);
$respondidas = count(array_filter($preguntas, static fn(array $p): bool => $p['respuesta_opcion'] !== null || $p['respuesta_texto'] !== ''));
?>
<form id="examen" method="post" action="examen.php" data-segundos="<?= $segundos ?>" data-intento="<?= (int)$intento['id'] ?>"
      data-csrf="<?= escapar(csrfToken()) ?>" data-total="<?= count($preguntas) ?>">
    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
    <input type="hidden" name="intento" value="<?= (int)$intento['id'] ?>">
    <input type="hidden" name="automatico" id="automatico" value="0">

    <div class="examen-barra" role="region" aria-label="Estado del examen">
        <div class="examen-tiempo" id="temporizador-caja">
            <i class="bi bi-stopwatch" aria-hidden="true"></i>
            <span class="visually-hidden">Tiempo restante:</span>
            <span id="temporizador" aria-live="off"><?= sprintf('%02d:%02d', intdiv($segundos, 60), $segundos % 60) ?></span>
        </div>
        <div class="examen-progreso">
            <span id="contador-respondidas"><?= $respondidas ?></span> de <?= count($preguntas) ?> respondidas
            <div class="progress mt-1" role="presentation"><div class="progress-bar" id="barra-progreso" style="width: <?= count($preguntas) ? round($respondidas / count($preguntas) * 100) : 0 ?>%"></div></div>
        </div>
        <span class="examen-guardado" id="estado-guardado" role="status" aria-live="polite"><i class="bi bi-cloud-check me-1" aria-hidden="true"></i>Autoguardado cada 30 s</span>
    </div>
    <p class="visually-hidden" id="aviso-tiempo" role="alert" aria-live="assertive"></p>

    <?php foreach ($preguntas as $numero => $pregunta): ?>
        <?php $pid = (int)$pregunta['id']; ?>
        <fieldset class="card-ui pregunta-card" data-pregunta="<?= $pid ?>">
            <legend class="pregunta-numero">
                Pregunta <?= $numero + 1 ?> <span class="text-secondary fw-normal small">· <?= escapar(rtrim(rtrim((string)$pregunta['puntaje'], '0'), '.')) ?> pt</span>
            </legend>
            <p class="pregunta-enunciado"><?= nl2br(escapar($pregunta['enunciado'])) ?></p>
            <?php if ($pregunta['tipo'] === 'respuesta_corta'): ?>
                <label class="visually-hidden" for="r-<?= $pid ?>">Tu respuesta a la pregunta <?= $numero + 1 ?></label>
                <input class="form-control" id="r-<?= $pid ?>" name="respuestas[<?= $pid ?>]" maxlength="<?= LONGITUD_MAXIMA_RESPUESTA ?>"
                       autocomplete="off" placeholder="Escribe tu respuesta" value="<?= escapar($pregunta['respuesta_texto']) ?>">
            <?php else: ?>
                <div class="opciones-examen">
                    <?php foreach ($pregunta['opciones'] as $opcion): ?>
                        <label class="opcion-examen">
                            <input class="form-check-input" type="radio" name="respuestas[<?= $pid ?>]" value="<?= (int)$opcion['id'] ?>"<?= $pregunta['respuesta_opcion'] === (int)$opcion['id'] ? ' checked' : '' ?>>
                            <span><?= escapar($opcion['texto']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <div class="card-ui d-flex flex-wrap align-items-center justify-content-between gap-3">
        <p class="mb-0 text-secondary small"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Al enviar ya no podrás cambiar tus respuestas.</p>
        <button class="btn btn-primary btn-lg" type="submit" id="boton-enviar"><i class="bi bi-send me-1" aria-hidden="true"></i>Enviar evaluación</button>
    </div>
</form>
