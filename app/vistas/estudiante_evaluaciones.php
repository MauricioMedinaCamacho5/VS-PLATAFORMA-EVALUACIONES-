<?php
// Tarjetas de evaluaciones del estudiante (lista general y dentro de una clase).
// Variables: $evaluaciones, $mensaje y opcional $mostrarClases (por defecto true).
$mostrarClases = $mostrarClases ?? true;
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<?php if (!$evaluaciones): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></span>
        <h2>No hay evaluaciones</h2>
        <p><?= $mostrarClases
                ? 'Cuando un docente publique una evaluación en una de tus clases, aparecerá aquí.'
                : 'Tu docente aún no ha publicado evaluaciones en esta clase.' ?></p>
        <?php if ($mostrarClases): ?><a class="btn btn-outline-primary" href="clases.php"><i class="bi bi-easel me-1" aria-hidden="true"></i>Ver mis clases</a><?php endif; ?>
    </section>
<?php else: ?>
    <div class="row g-3 g-lg-4">
        <?php foreach ($evaluaciones as $evaluacion): ?>
            <?php
            [$etiqueta, $claseEstado] = estadoParaEstudiante($evaluacion['estado']);
            $idEval = (int)$evaluacion['id'];
            $usados = (int)$evaluacion['intentos_usados'];
            $permitidos = (int)$evaluacion['intentos_permitidos'];
            $abierta = $evaluacion['estado'] === 'abierta';
            $enCurso = $evaluacion['intento_en_curso'] !== null;
            $puedePresentar = $abierta && ($enCurso || $usados < $permitidos);
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <article class="card-ui card-hover eval-card<?= $abierta ? '' : ' eval-card-inactiva' ?>" aria-labelledby="eval-<?= $idEval ?>">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge-estado <?= escapar($claseEstado) ?>"><?= escapar($etiqueta) ?></span>
                        <?php if ($enCurso): ?><span class="badge-estado badge-proxima">En curso</span><?php endif; ?>
                        <?php if ($abierta && !$enCurso && $usados >= $permitidos): ?><span class="badge-estado badge-borrador">Sin intentos</span><?php endif; ?>
                    </div>
                    <h2 class="eval-card-title" id="eval-<?= $idEval ?>"><?= escapar($evaluacion['titulo']) ?></h2>
                    <p class="eval-card-docente">
                        <i class="bi bi-person-badge me-1" aria-hidden="true"></i><?= escapar($evaluacion['docente']) ?>
                        <?php if ($mostrarClases && $evaluacion['clases']): ?><br><i class="bi bi-easel me-1" aria-hidden="true"></i><?= escapar($evaluacion['clases']) ?><?php endif; ?>
                    </p>
                    <dl class="eval-meta">
                        <div><dt><i class="bi bi-stopwatch" aria-hidden="true"></i>Tiempo límite</dt><dd><?= (int)$evaluacion['tiempo_minutos'] ?> min</dd></div>
                        <div>
                            <dt><i class="bi bi-calendar-event" aria-hidden="true"></i><?= $evaluacion['estado'] === 'proxima' ? 'Abre' : 'Cierre' ?></dt>
                            <dd><?= escapar(formatoFecha($evaluacion['estado'] === 'proxima' ? $evaluacion['apertura'] : $evaluacion['cierre'])) ?></dd>
                        </div>
                        <div><dt><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Intentos</dt><dd><?= $usados ?> de <?= $permitidos ?></dd></div>
                    </dl>
                    <div class="d-grid gap-2 mt-auto">
                        <?php if ($enCurso && $abierta): ?>
                            <a class="btn btn-primary" href="examen.php?intento=<?= (int)$evaluacion['intento_en_curso'] ?>"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Continuar</a>
                        <?php elseif ($puedePresentar): ?>
                            <a class="btn btn-primary" href="presentar.php?id=<?= $idEval ?>" aria-label="Presentar <?= escapar($evaluacion['titulo']) ?>"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Presentar</a>
                        <?php else: ?>
                            <a class="btn btn-outline-primary" href="presentar.php?id=<?= $idEval ?>" aria-label="Ver instrucciones de <?= escapar($evaluacion['titulo']) ?>"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Ver instrucciones</a>
                        <?php endif; ?>
                        <?php if ($evaluacion['ultimo_intento'] !== null): ?>
                            <a class="btn btn-sm btn-outline-success" href="resultado.php?intento=<?= (int)$evaluacion['ultimo_intento'] ?>"><i class="bi bi-award me-1" aria-hidden="true"></i>Ver mi nota</a>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
