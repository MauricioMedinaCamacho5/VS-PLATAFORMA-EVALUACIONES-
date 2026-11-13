<?php
// Instrucciones de una evaluación para el estudiante. Variable: $evaluacion.
[$etiqueta, $claseEstado] = estadoParaEstudiante($evaluacion['estado']);
$usados = (int)$evaluacion['intentos_usados'];
$permitidos = (int)$evaluacion['intentos_permitidos'];
$restantes = max(0, $permitidos - $usados);
$nota = rtrim(rtrim(number_format((float)$evaluacion['nota_minima'], 2, ',', '.'), '0'), ',');
?>
<p><a class="btn btn-sm btn-outline-primary" href="evaluaciones.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a Mis evaluaciones</a></p>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php'; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="card-ui" aria-labelledby="instrucciones-titulo">
            <div class="section-header">
                <h2 class="card-title-ui" id="instrucciones-titulo"><i class="bi bi-journal-text" aria-hidden="true"></i>Instrucciones</h2>
                <span class="badge-estado <?= escapar($claseEstado) ?>"><?= escapar($etiqueta) ?></span>
            </div>

            <?php if (trim($evaluacion['descripcion']) !== ''): ?>
                <p class="instrucciones-texto"><?= nl2br(escapar($evaluacion['descripcion'])) ?></p>
            <?php else: ?>
                <p class="text-secondary">El docente no añadió una descripción para esta evaluación.</p>
            <?php endif; ?>

            <ul class="reglas">
                <li><i class="bi bi-stopwatch" aria-hidden="true"></i><span>Tendrás <strong><?= (int)$evaluacion['tiempo_minutos'] ?> minutos</strong> desde que empieces (o hasta el cierre, si llega antes). Al acabarse el tiempo se envía sola.</span></li>
                <li><i class="bi bi-cloud-check" aria-hidden="true"></i><span>Tus respuestas se guardan automáticamente cada 30 segundos.</span></li>
                <li><i class="bi bi-arrow-repeat" aria-hidden="true"></i><span>Puedes presentarla <strong><?= $permitidos ?> <?= $permitidos === 1 ? 'vez' : 'veces' ?></strong>. Te <?= $restantes === 1 ? 'queda' : 'quedan' ?> <strong><?= $restantes ?></strong>.</span></li>
                <li><i class="bi bi-award" aria-hidden="true"></i><span>Nota mínima para aprobar: <strong><?= escapar($nota) ?></strong>.</span></li>
                <?php if ((int)$evaluacion['orden_aleatorio'] === 1): ?>
                    <li><i class="bi bi-shuffle" aria-hidden="true"></i><span>Las preguntas aparecerán en orden aleatorio.</span></li>
                <?php endif; ?>
                <li><i class="bi bi-chat-square-text" aria-hidden="true"></i><span><?= (int)$evaluacion['mostrar_retroalimentacion'] === 1
                        ? 'Al terminar verás la retroalimentación de tus respuestas.'
                        : 'Esta evaluación no muestra retroalimentación al terminar.' ?></span></li>
                <li><i class="bi bi-wifi" aria-hidden="true"></i><span>Asegúrate de tener buena conexión antes de empezar.</span></li>
            </ul>
        </section>
    </div>

    <div class="col-lg-4">
        <aside class="card-ui" aria-labelledby="resumen-titulo">
            <h2 class="card-title-ui mb-3" id="resumen-titulo"><i class="bi bi-info-circle" aria-hidden="true"></i>Resumen</h2>
            <dl class="eval-meta eval-meta-vertical">
                <div><dt><i class="bi bi-calendar-check" aria-hidden="true"></i>Abre</dt><dd><?= escapar(formatoFecha($evaluacion['apertura'])) ?></dd></div>
                <div><dt><i class="bi bi-calendar-x" aria-hidden="true"></i>Cierra</dt><dd><?= escapar(formatoFecha($evaluacion['cierre'])) ?></dd></div>
                <div><dt><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Intentos</dt><dd><?= $usados ?> de <?= $permitidos ?></dd></div>
            </dl>

            <?php if ($evaluacion['intento_en_curso'] !== null && $evaluacion['estado'] === 'abierta'): ?>
                <a class="btn btn-primary w-100 mt-3" href="examen.php?intento=<?= (int)$evaluacion['intento_en_curso'] ?>">
                    <i class="bi bi-play-circle me-1" aria-hidden="true"></i>Continuar mi intento
                </a>
                <p class="small text-secondary text-center mt-2 mb-0">El temporizador sigue corriendo desde que empezaste.</p>
            <?php elseif ($evaluacion['estado'] === 'proxima'): ?>
                <div class="alert alert-warning mt-3 mb-0 small">Esta evaluación todavía no abre. Vuelve a partir del <?= escapar(formatoFecha($evaluacion['apertura'])) ?>.</div>
            <?php elseif ($evaluacion['estado'] === 'cerrada'): ?>
                <div class="alert alert-secondary mt-3 mb-0 small">Esta evaluación ya cerró y no se puede presentar.</div>
            <?php elseif ($restantes === 0): ?>
                <div class="alert alert-secondary mt-3 mb-0 small">Ya usaste todos tus intentos.</div>
            <?php elseif ((int)$evaluacion['total_preguntas'] === 0): ?>
                <div class="alert alert-secondary mt-3 mb-0 small">El docente aún no ha añadido preguntas.</div>
            <?php else: ?>
                <form method="post" action="presentar.php" class="mt-3">
                    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$evaluacion['id'] ?>">
                    <button class="btn btn-primary btn-lg w-100" type="submit" aria-describedby="comenzar-nota">
                        <i class="bi bi-play-circle me-1" aria-hidden="true"></i>Iniciar evaluación
                    </button>
                </form>
                <p class="small text-secondary text-center mt-2 mb-0" id="comenzar-nota">
                    <?= (int)$evaluacion['total_preguntas'] ?> pregunta<?= (int)$evaluacion['total_preguntas'] === 1 ? '' : 's' ?>. El tiempo empieza a contar al pulsar.
                </p>
            <?php endif; ?>
            <?php if ($evaluacion['ultimo_intento'] !== null): ?>
                <a class="btn btn-sm btn-outline-success w-100 mt-2" href="resultado.php?intento=<?= (int)$evaluacion['ultimo_intento'] ?>"><i class="bi bi-award me-1" aria-hidden="true"></i>Ver mi última nota</a>
            <?php endif; ?>
        </aside>
    </div>
</div>
