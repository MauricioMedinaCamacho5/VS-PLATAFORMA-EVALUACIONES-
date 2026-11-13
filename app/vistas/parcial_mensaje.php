<?php
// Mensaje de una sola vez (flash). Variable: $mensaje = ['texto' => ..., 'tipo' => ...] o null.
if (isset($mensaje) && is_array($mensaje)):
    $tipoMensaje = in_array($mensaje['tipo'] ?? '', ['success', 'info', 'warning', 'danger'], true) ? $mensaje['tipo'] : 'info';
    $iconoMensaje = ['success' => 'bi-check-circle-fill', 'info' => 'bi-info-circle-fill', 'warning' => 'bi-exclamation-triangle-fill', 'danger' => 'bi-x-circle-fill'][$tipoMensaje];
    ?>
    <div class="alert alert-<?= escapar($tipoMensaje) ?> d-flex align-items-start gap-2" role="status">
        <i class="bi <?= escapar($iconoMensaje) ?> mt-1" aria-hidden="true"></i><span><?= escapar((string)$mensaje['texto']) ?></span>
    </div>
<?php endif; ?>
