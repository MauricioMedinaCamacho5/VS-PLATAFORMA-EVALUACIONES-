<?php
// Resultado del intento. Variables: $intento, $detalle, $usados, $mensaje.
// Muestra si cada respuesta fue correcta y la retroalimentación, pero no cuál era la respuesta correcta.
$nota = (float)$intento['nota'];
$aprobado = $nota >= (float)$intento['nota_minima'];
$formatear = static fn(float $n): string => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
$restantes = max(0, (int)$intento['intentos_permitidos'] - $usados);
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <section class="card-ui resultado-card <?= $aprobado ? 'resultado-aprobado' : 'resultado-reprobado' ?>" aria-labelledby="nota-titulo">
            <h2 class="visually-hidden" id="nota-titulo">Tu nota</h2>
            <span class="resultado-icono" aria-hidden="true"><i class="bi <?= $aprobado ? 'bi-trophy-fill' : 'bi-emoji-neutral' ?>"></i></span>
            <p class="nota-grande"><?= escapar($formatear($nota)) ?><span>/<?= NOTA_MAXIMA ?></span></p>
            <span class="badge-estado <?= $aprobado ? 'badge-publicada' : 'badge-cerrada' ?>"><?= $aprobado ? 'Aprobado' : 'No aprobado' ?></span>
            <p class="small text-secondary mt-3 mb-0">Nota mínima: <?= escapar($formatear((float)$intento['nota_minima'])) ?> · Enviado el <?= escapar(formatoFecha((string)$intento['enviado_en'])) ?></p>
            <p class="small text-secondary mb-0">Intentos restantes: <?= $restantes ?></p>
            <div class="d-grid gap-2 mt-4">
                <?php if ($restantes > 0): ?>
                    <a class="btn btn-outline-primary" href="presentar.php?id=<?= (int)$intento['evaluacion_id'] ?>"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Volver a intentarlo</a>
                <?php endif; ?>
                <a class="btn btn-primary" href="clases.php"><i class="bi bi-easel me-1" aria-hidden="true"></i>Volver a mis clases</a>
            </div>
        </section>
    </div>
    <div class="col-lg-8">
        <?php if (!$detalle): ?>
            <section class="card-ui empty-state">
                <span class="empty-icon" aria-hidden="true"><i class="bi bi-eye-slash"></i></span>
                <h2>Sin retroalimentación</h2>
                <p class="mb-0">Tu docente configuró esta evaluación para mostrar solo la nota.</p>
            </section>
        <?php else: ?>
            <section class="card-ui" aria-labelledby="detalle-titulo">
                <h2 class="card-title-ui mb-3" id="detalle-titulo"><i class="bi bi-chat-square-text" aria-hidden="true"></i>Retroalimentación</h2>
                <ol class="lista-preguntas">
                    <?php foreach ($detalle as $fila): ?>
                        <?php
                        $acerto = (float)$fila['puntaje_obtenido'] > 0;
                        $respuesta = $fila['tipo'] === 'respuesta_corta' ? $fila['respuesta_texto'] : $fila['respuesta_opcion'];
                        ?>
                        <li class="pregunta-item resultado-<?= $acerto ? 'bien' : 'mal' ?>">
                            <p class="pregunta-enunciado mb-1"><?= nl2br(escapar($fila['enunciado'])) ?></p>
                            <p class="small mb-1">
                                <i class="bi <?= $acerto ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> me-1" aria-hidden="true"></i>
                                <strong><?= $acerto ? 'Correcta' : 'Incorrecta' ?></strong>
                                · <?= escapar($formatear((float)$fila['puntaje_obtenido'])) ?> de <?= escapar($formatear((float)$fila['puntaje'])) ?> pt
                            </p>
                            <p class="small text-secondary mb-1">Tu respuesta: <?= $respuesta !== null && $respuesta !== '' ? '<strong>' . escapar((string)$respuesta) . '</strong>' : '<em>sin responder</em>' ?></p>
                            <?php if ($fila['retroalimentacion'] !== null && trim($fila['retroalimentacion']) !== ''): ?>
                                <p class="retro mb-0"><i class="bi bi-lightbulb me-1" aria-hidden="true"></i><?= nl2br(escapar($fila['retroalimentacion'])) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>
    </div>
</div>
