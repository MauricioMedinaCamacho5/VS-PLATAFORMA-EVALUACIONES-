<?php
// Historial de resultados. Variables: $resultados y $mensaje.
$formatearNota = static fn(float $nota): string => rtrim(rtrim(number_format($nota, 2, ',', '.'), '0'), ',');
require __DIR__ . DIRECTORY_SEPARATOR . 'parcial_mensaje.php';
?>
<?php if (!$resultados): ?>
    <section class="card-ui empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
        <h2>Aún no tienes resultados</h2>
        <p>Cuando completes una evaluación, tu calificación aparecerá aquí.</p>
        <a class="btn btn-primary" href="evaluaciones.php"><i class="bi bi-clipboard-check me-1" aria-hidden="true"></i>Ver mis evaluaciones</a>
    </section>
<?php else: ?>
    <section class="card-ui table-card" aria-labelledby="resultados-titulo">
        <div class="section-header">
            <h2 class="card-title-ui" id="resultados-titulo"><i class="bi bi-award-fill" aria-hidden="true"></i>Historial de calificaciones</h2>
            <a class="btn btn-sm btn-outline-primary" href="evaluaciones.php">Mis evaluaciones <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-ui table-hover align-middle">
                <thead>
                <tr>
                    <th scope="col">Evaluación</th>
                    <th scope="col" class="d-none d-md-table-cell">Docente</th>
                    <th scope="col" class="d-none d-sm-table-cell">Intento</th>
                    <th scope="col" class="d-none d-lg-table-cell">Fecha</th>
                    <th scope="col">Nota</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($resultados as $resultado): ?>
                    <?php
                    $nota = (float)$resultado['nota'];
                    $aprobado = $nota >= (float)$resultado['nota_minima'];
                    ?>
                    <tr>
                        <th scope="row" class="titulo-evaluacion">
                            <?= escapar($resultado['titulo']) ?>
                            <span class="d-block fw-normal small text-secondary mt-1 d-md-none"><?= escapar($resultado['docente']) ?></span>
                        </th>
                        <td class="d-none d-md-table-cell"><?= escapar($resultado['docente']) ?></td>
                        <td class="d-none d-sm-table-cell">#<?= (int)$resultado['numero'] ?></td>
                        <td class="d-none d-lg-table-cell text-nowrap text-secondary"><?= escapar(formatoFecha((string)$resultado['enviado_en'])) ?></td>
                        <td class="text-nowrap"><strong><?= escapar($formatearNota($nota)) ?>/<?= NOTA_MAXIMA ?></strong></td>
                        <td>
                            <span class="badge-estado <?= $aprobado ? 'badge-publicada' : 'badge-cerrada' ?>">
                                <?= $aprobado ? 'Aprobado' : 'No aprobado' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="resultado.php?intento=<?= (int)$resultado['id'] ?>">
                                <span class="d-none d-sm-inline">Ver resultado</span>
                                <span class="d-sm-none">Ver</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
