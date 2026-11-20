<?php
// Resultados de evaluaciones propias. Variables: $evaluaciones, $evaluacionId, $resultados, $resumen,
// $totalResultados, $pagina, $totalPaginas, $detalleIntento, $detallePreguntas y $mensaje.
$formatear = static fn(float $nota): string => rtrim(rtrim(number_format($nota, 2, ',', '.'), '0'), ',');
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<section class="card-ui mb-4" aria-label="Filtros y resumen de resultados">
    <div class="row align-items-end g-3">
        <div class="col-md-6">
            <label class="form-label" for="filtro-evaluacion">Filtrar por evaluación</label>
            <form method="get" action="resultados.php" class="d-flex gap-2">
                <select class="form-select" id="filtro-evaluacion" name="evaluacion">
                    <option value="">Todas mis evaluaciones</option>
                    <?php foreach ($evaluaciones as $evaluacion): ?>
                        <option value="<?= (int)$evaluacion['id'] ?>"<?= $evaluacionId === (int)$evaluacion['id'] ? ' selected' : '' ?>><?= escapar($evaluacion['titulo']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Filtrar</button>
            </form>
        </div>
        <div class="col-md-3">
            <span class="d-block text-secondary small">Intentos calificados</span>
            <strong><?= $totalResultados ?></strong>
        </div>
        <div class="col-md-3">
            <span class="d-block text-secondary small">Promedio<?= $evaluacionId === null ? ' general' : '' ?></span>
            <strong><?= $resumen['promedio'] !== null ? escapar($formatear((float)$resumen['promedio'])) . '/' . NOTA_MAXIMA : '—' ?></strong>
        </div>
    </div>
</section>

<?php if ($detalleIntento !== null): ?>
    <section class="card-ui mb-4" aria-labelledby="detalle-intento-titulo">
        <div class="section-header">
            <div>
                <h2 class="card-title-ui mb-1" id="detalle-intento-titulo">Respuestas del estudiante</h2>
                <p class="mb-0 text-secondary"><?= escapar($detalleIntento['estudiante']) ?> · <?= escapar($detalleIntento['correo']) ?> · <?= escapar($detalleIntento['titulo']) ?> · intento #<?= (int)$detalleIntento['numero'] ?></p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="resultados.php<?= $evaluacionId === null ? '' : '?evaluacion=' . $evaluacionId ?>">Cerrar detalle</a>
        </div>
        <p class="mt-3 mb-0"><strong>Nota:</strong> <?= escapar($formatear((float)$detalleIntento['nota'])) ?>/<?= NOTA_MAXIMA ?>
            · <span class="text-secondary">Enviado el <?= escapar(formatoFecha((string)$detalleIntento['enviado_en'])) ?></span></p>
        <?php if (!$detallePreguntas): ?>
            <p class="text-secondary mt-3 mb-0">Esta evaluación no tiene preguntas.</p>
        <?php else: ?>
            <ol class="lista-preguntas mt-3">
                <?php foreach ($detallePreguntas as $fila): ?>
                    <?php
                    $respuesta = $fila['tipo'] === 'respuesta_corta' ? $fila['respuesta_texto'] : $fila['respuesta_opcion'];
                    $obtenido = $fila['puntaje_obtenido'] === null ? 0.0 : (float)$fila['puntaje_obtenido'];
                    ?>
                    <li class="pregunta-item">
                        <p class="pregunta-enunciado mb-2"><?= nl2br(escapar($fila['enunciado'])) ?></p>
                        <p class="small mb-1">Respuesta: <?= $respuesta !== null && $respuesta !== ''
                            ? '<strong>' . escapar((string)$respuesta) . '</strong>' : '<em>Sin responder</em>' ?></p>
                        <?php if ($fila['tipo'] === 'respuesta_corta'): ?>
                            <p class="small text-secondary mb-1">Respuesta esperada: <?= escapar((string)$fila['respuesta_corta']) ?></p>
                        <?php elseif ($fila['opcion_correcta'] !== null): ?>
                            <p class="small text-secondary mb-1">Opción correcta: <?= escapar($fila['opcion_correcta']) ?></p>
                        <?php endif; ?>
                        <span class="badge-estado <?= $obtenido > 0 ? 'badge-publicada' : 'badge-cerrada' ?>">
                            <?= escapar($formatear($obtenido)) ?> de <?= escapar($formatear((float)$fila['puntaje'])) ?> pt
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if (!$resultados): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-bar-chart-line"></i></span>
        <h2>Aún no hay resultados</h2>
        <p>Cuando tus estudiantes completen una evaluación, sus notas aparecerán aquí.</p>
        <a class="btn btn-primary" href="evaluaciones.php">Ver mis evaluaciones</a>
    </section>
<?php else: ?>
    <section class="card-ui table-card" aria-labelledby="tabla-resultados-titulo">
        <div class="section-header">
            <h2 class="card-title-ui" id="tabla-resultados-titulo"><i class="bi bi-award" aria-hidden="true"></i>Calificaciones</h2>
            <span class="small text-secondary">Página <?= $pagina ?> de <?= $totalPaginas ?> · hasta 100 por página</span>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-ui table-hover align-middle">
                <thead>
                <tr>
                    <th scope="col">Estudiante</th>
                    <th scope="col">Evaluación</th>
                    <th scope="col">Intento</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">Nota</th>
                    <th scope="col">Estado</th>
                    <th scope="col"><span class="visually-hidden">Acciones</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($resultados as $resultado): ?>
                    <?php $aprobado = (float)$resultado['nota'] >= (float)$resultado['nota_minima']; ?>
                    <tr>
                        <th scope="row">
                            <?= escapar($resultado['estudiante']) ?>
                            <span class="d-block fw-normal small text-secondary"><?= escapar($resultado['correo']) ?></span>
                        </th>
                        <td><?= escapar($resultado['titulo']) ?></td>
                        <td>#<?= (int)$resultado['numero'] ?></td>
                        <td class="text-nowrap"><?= escapar(formatoFecha((string)$resultado['enviado_en'])) ?></td>
                        <td class="text-nowrap"><strong><?= escapar($formatear((float)$resultado['nota'])) ?>/<?= NOTA_MAXIMA ?></strong></td>
                        <td><span class="badge-estado <?= $aprobado ? 'badge-publicada' : 'badge-cerrada' ?>"><?= $aprobado ? 'Aprobado' : 'No aprobado' ?></span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="resultados.php?intento=<?= (int)$resultado['id'] ?><?= $evaluacionId === null ? '' : '&amp;evaluacion=' . $evaluacionId ?>">Ver respuestas</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPaginas > 1): ?>
            <nav class="d-flex justify-content-between mt-3" aria-label="Paginación de resultados">
                <?php if ($pagina > 1): ?>
                    <a class="btn btn-sm btn-outline-primary" href="resultados.php?pagina=<?= $pagina - 1 ?><?= $evaluacionId === null ? '' : '&amp;evaluacion=' . $evaluacionId ?>">Anterior</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <?php if ($pagina < $totalPaginas): ?>
                    <a class="btn btn-sm btn-outline-primary" href="resultados.php?pagina=<?= $pagina + 1 ?><?= $evaluacionId === null ? '' : '&amp;evaluacion=' . $evaluacionId ?>">Siguiente</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
<?php endif; ?>
