<?php
// Vista del formulario de evaluación. Variables: $valores, $errores, $editando, $publicada, $evaluacionId.
$campo = static fn(string $nombre): string => isset($errores[$nombre]) ? ' is-invalid' : '';
$error = static fn(string $nombre): string => isset($errores[$nombre])
    ? '<div class="invalid-feedback d-block" id="error-' . escapar($nombre) . '">' . escapar($errores[$nombre]) . '</div>'
    : '';
$describe = static function (string $nombre, string $extra = '') use ($errores): string {
    $ids = trim($extra . (isset($errores[$nombre]) ? ' error-' . $nombre : ''));
    return $ids === '' ? '' : ' aria-describedby="' . escapar($ids) . '"';
};
?>
<link rel="stylesheet" href="../assets/vendor/flatpickr/flatpickr.min.css">
<style>
/* Estilos modernos y elegantes para el selector de fecha y hora (Flatpickr) */
.flatpickr-calendar {
    font-family: 'Poppins', sans-serif !important;
    border-radius: 18px !important;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16) !important;
    border: 1px solid #E2E8F0 !important;
    padding: 10px !important;
    background: #ffffff !important;
}
.flatpickr-months {
    border-radius: 12px 12px 0 0 !important;
    padding-bottom: 6px !important;
}
.flatpickr-month {
    color: #1E293B !important;
    font-weight: 600 !important;
}
.flatpickr-current-month .cur-month {
    font-weight: 600 !important;
}
.flatpickr-weekdays {
    margin-bottom: 6px !important;
}
span.flatpickr-weekday {
    color: #64748B !important;
    font-weight: 600 !important;
    font-size: 0.78rem !important;
}
.flatpickr-day {
    border-radius: 10px !important;
    font-weight: 500 !important;
    color: #1E293B !important;
    transition: all 0.2s ease !important;
}
.flatpickr-day:hover {
    background: #EFF6FF !important;
    color: #2563EB !important;
}
.flatpickr-day.today {
    border-color: #2563EB !important;
    color: #2563EB !important;
}
.flatpickr-day.selected, .flatpickr-day.selected:hover {
    background: linear-gradient(135deg, #2563EB, #1D4ED8) !important;
    color: #ffffff !important;
    border-color: #2563EB !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35) !important;
}
.flatpickr-time {
    border-top: 1px solid #F1F5F9 !important;
    margin-top: 8px !important;
    padding-top: 8px !important;
    border-radius: 0 0 12px 12px !important;
    background: #F8FAFC !important;
}
.flatpickr-time input {
    font-weight: 600 !important;
    font-size: 1.1rem !important;
    color: #1E293B !important;
}
.flatpickr-time .flatpickr-am-pm {
    font-weight: 700 !important;
    color: #2563EB !important;
    background: #EFF6FF !important;
    border-radius: 8px !important;
    padding: 2px 6px !important;
}
.flatpickr-time .flatpickr-am-pm:hover {
    background: #DBEAFE !important;
}
.flatpickr-custom-input {
    background-color: #ffffff !important;
    cursor: pointer !important;
    font-weight: 500 !important;
}
.flatpickr-custom-input:focus {
    border-color: #2563EB !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
}
</style>
<p><a class="btn btn-sm btn-outline-primary" href="evaluaciones.php"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a Mis evaluaciones</a></p>

<?php if ($errores): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Revisa los datos del formulario:</strong>
        <ul class="mb-0">
            <?php foreach ($errores as $mensajeError): ?><li><?= escapar($mensajeError) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($publicada): ?>
    <div class="alert alert-warning" role="status">Esta evaluación está <strong>publicada</strong>: los cambios serán visibles para los estudiantes al guardar.</div>
<?php endif; ?>

<form class="card-ui evaluacion-form" method="post" action="evaluacion_form.php" novalidate>
    <input type="hidden" name="csrf" value="<?= escapar(csrfToken()) ?>">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$evaluacionId ?>"><?php endif; ?>

    <div class="mb-3">
        <label class="form-label" for="titulo">Título <span aria-hidden="true">*</span></label>
        <input class="form-control<?= $campo('titulo') ?>" id="titulo" name="titulo" maxlength="200" required
               value="<?= escapar($valores['titulo']) ?>"<?= $describe('titulo') ?>>
        <?= $error('titulo') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea class="form-control<?= $campo('descripcion') ?>" id="descripcion" name="descripcion" rows="4"
                  maxlength="5000"<?= $describe('descripcion') ?>><?= escapar($valores['descripcion']) ?></textarea>
        <?= $error('descripcion') ?>
    </div>

    <div class="row g-3">
        <div class="col-sm-6">
            <label class="form-label d-flex justify-content-between align-items-center" for="apertura">
                <span><i class="bi bi-calendar-check text-primary me-1"></i>Fecha de apertura <span aria-hidden="true">*</span></span>
                <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.75rem;">Hora Colombia</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-primary"><i class="bi bi-clock-history"></i></span>
                <input class="form-control border-start-0 ps-1<?= $campo('apertura') ?>" id="apertura" name="apertura" type="datetime-local" required
                       value="<?= escapar($valores['apertura']) ?>"<?= $describe('apertura') ?>>
            </div>
            <?= $error('apertura') ?>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="small text-muted align-self-center me-1" style="font-size: 0.78rem;">Atajos:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="apertura" data-opcion="ahora" style="font-size: 0.78rem; border-radius: 999px;">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Ahora mismo
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="apertura" data-opcion="hoy_tarde" style="font-size: 0.78rem; border-radius: 999px;">
                    Hoy 2:00 PM
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="apertura" data-opcion="manana_8am" style="font-size: 0.78rem; border-radius: 999px;">
                    Mañana 8:00 AM
                </button>
            </div>
        </div>

        <div class="col-sm-6">
            <label class="form-label d-flex justify-content-between align-items-center" for="cierre">
                <span><i class="bi bi-calendar-x text-danger me-1"></i>Fecha de cierre <span aria-hidden="true">*</span></span>
                <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.75rem;">Hora Colombia</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-danger"><i class="bi bi-alarm"></i></span>
                <input class="form-control border-start-0 ps-1<?= $campo('cierre') ?>" id="cierre" name="cierre" type="datetime-local" required
                       value="<?= escapar($valores['cierre']) ?>"<?= $describe('cierre') ?>>
            </div>
            <?= $error('cierre') ?>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="small text-muted align-self-center me-1" style="font-size: 0.78rem;">Extender:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="cierre" data-opcion="+2h" style="font-size: 0.78rem; border-radius: 999px;">+2 horas</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="cierre" data-opcion="+24h" style="font-size: 0.78rem; border-radius: 999px;">+24 horas</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="cierre" data-opcion="+3d" style="font-size: 0.78rem; border-radius: 999px;">+3 días</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-atajo-fecha" data-target="cierre" data-opcion="+7d" style="font-size: 0.78rem; border-radius: 999px;">+1 semana</button>
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="p-2 px-3 rounded-3 d-flex align-items-center gap-2" id="resumen-fechas-banner" style="background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.18); font-size: 0.88rem;">
                <i class="bi bi-info-circle-fill text-primary" style="font-size: 1.15rem;"></i>
                <div id="resumen-fechas-texto" class="text-dark">Calculando disponibilidad...</div>
            </div>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="tiempo_minutos">Tiempo límite (minutos) <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('tiempo_minutos') ?>" id="tiempo_minutos" name="tiempo_minutos" type="number"
                   min="1" max="<?= TIEMPO_MAXIMO_MINUTOS ?>" step="1" required
                   value="<?= escapar($valores['tiempo_minutos']) ?>"<?= $describe('tiempo_minutos') ?>>
            <?= $error('tiempo_minutos') ?>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="intentos_permitidos">Intentos permitidos <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('intentos_permitidos') ?>" id="intentos_permitidos" name="intentos_permitidos"
                   type="number" min="1" max="<?= INTENTOS_MAXIMOS ?>" step="1" required
                   value="<?= escapar($valores['intentos_permitidos']) ?>"<?= $describe('intentos_permitidos') ?>>
            <?= $error('intentos_permitidos') ?>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="nota_minima">Nota mínima para aprobar <span aria-hidden="true">*</span></label>
            <input class="form-control<?= $campo('nota_minima') ?>" id="nota_minima" name="nota_minima" type="number"
                   min="0" max="<?= NOTA_MAXIMA ?>" step="0.01" required
                   value="<?= escapar($valores['nota_minima']) ?>"<?= $describe('nota_minima', 'ayuda-nota') ?>>
            <div class="form-text" id="ayuda-nota">Escala de 0 a <?= NOTA_MAXIMA ?>.</div>
            <?= $error('nota_minima') ?>
        </div>
    </div>

    <fieldset class="mt-4 asignacion"<?= $describe('clases') ?>>
        <legend class="form-label fs-6 fw-semibold mb-1"><i class="bi bi-easel me-1" aria-hidden="true"></i>¿En qué clases?</legend>
        <p class="form-text mt-0 mb-3">Los estudiantes de las clases marcadas la verán cuando esté <strong>publicada</strong>.</p>
        <?php if ($clasesPropias): ?>
            <div class="grupos-lista">
                <?php foreach ($clasesPropias as $claseOpcion): ?>
                    <?php $cid = (int)$claseOpcion['id']; ?>
                    <div class="form-check grupo-opcion" style="--clase-color: <?= escapar($claseOpcion['color']) ?>">
                        <input class="form-check-input" type="checkbox" id="clase-<?= $cid ?>" name="clases[]" value="<?= $cid ?>"<?= in_array($cid, $valores['clases'], true) ? ' checked' : '' ?>>
                        <label class="form-check-label" for="clase-<?= $cid ?>">
                            <span class="punto-color" aria-hidden="true"></span><?= escapar($claseOpcion['nombre']) ?>
                            <span class="d-block text-secondary small"><?= (int)$claseOpcion['estudiantes'] ?> estudiante<?= (int)$claseOpcion['estudiantes'] === 1 ? '' : 's' ?><?= $claseOpcion['institucion'] ? ' · ' . escapar($claseOpcion['institucion']) : '' ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small text-secondary mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Aún no tienes clases. <a href="clase_form.php">Crea una clase</a> y vuelve para asignarla.</p>
        <?php endif; ?>
        <?= $error('clases') ?>
    </fieldset>

    <fieldset class="mt-4">
        <legend class="form-label fs-6 fw-semibold mb-2">Opciones de publicación y examen</legend>
        <div class="form-check form-switch mb-3 p-3 px-4 rounded-3 border bg-light">
            <input class="form-check-input" type="checkbox" role="switch" id="publicada" name="publicada" value="1"<?= ($publicada ?? true) ? ' checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="publicada">
                <i class="bi bi-broadcast text-primary me-1"></i>Publicar evaluación
                <span class="d-block text-secondary small fw-normal">Los estudiantes de las clases asignadas podrán presentarla de inmediato cuando llegue la fecha y hora de apertura fijada arriba.</span>
            </label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="orden_aleatorio" name="orden_aleatorio" value="1"<?= $valores['orden_aleatorio'] ? ' checked' : '' ?>>
            <label class="form-check-label" for="orden_aleatorio">Mostrar las preguntas en orden aleatorio</label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="mostrar_retroalimentacion" name="mostrar_retroalimentacion" value="1"<?= $valores['mostrar_retroalimentacion'] ? ' checked' : '' ?>>
            <label class="form-check-label" for="mostrar_retroalimentacion">Mostrar retroalimentación y resultados al estudiante</label>
        </div>
    </fieldset>

    <p class="form-text mt-3 mb-0"><span aria-hidden="true">*</span> Campos obligatorios. Horarios sincronizados con la hora oficial de Colombia.</p>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary" type="submit"><?= $editando ? 'Guardar cambios' : 'Guardar evaluación' ?></button>
        <a class="btn btn-outline-secondary" href="evaluaciones.php">Cancelar</a>
    </div>
</form>

<script src="../assets/vendor/flatpickr/flatpickr.min.js"></script>
<script src="../assets/vendor/flatpickr/es.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputApertura = document.getElementById('apertura');
    const inputCierre = document.getElementById('cierre');
    const textoResumen = document.getElementById('resumen-fechas-texto');
    const bannerResumen = document.getElementById('resumen-fechas-banner');

    function formatearFechaBonita(date) {
        return date.toLocaleString('es-CO', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    function actualizarResumen() {
        if (!inputApertura || !inputCierre || !textoResumen || !bannerResumen) return;
        if (!inputApertura.value || !inputCierre.value) {
            textoResumen.textContent = 'Indica la fecha y hora de apertura y cierre para ver el estado de disponibilidad.';
            return;
        }

        const ahora = new Date();
        const apertura = new Date(inputApertura.value);
        const cierre = new Date(inputCierre.value);

        if (isNaN(apertura.getTime()) || isNaN(cierre.getTime())) {
            textoResumen.textContent = 'Fechas incompletas o no válidas.';
            return;
        }

        if (cierre <= apertura) {
            bannerResumen.style.background = 'rgba(239, 68, 68, 0.08)';
            bannerResumen.style.borderColor = 'rgba(239, 68, 68, 0.25)';
            textoResumen.innerHTML = '<strong class="text-danger">⚠️ Error en fechas:</strong> La fecha de cierre debe ser posterior a la fecha de apertura.';
            return;
        }

        bannerResumen.style.background = 'rgba(37, 99, 235, 0.06)';
        bannerResumen.style.borderColor = 'rgba(37, 99, 235, 0.18)';

        const diffMs = cierre - apertura;
        const diffHoras = (diffMs / (1000 * 60 * 60)).toFixed(1);
        const dias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const horasRest = Math.round((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let duracionTxt = '';
        if (dias > 0) duracionTxt += `${dias} día${dias > 1 ? 's' : ''} `;
        if (horasRest > 0 || dias === 0) duracionTxt += `${horasRest} hora${horasRest !== 1 ? 's' : ''}`;

        let estadoTxt = '';
        if (apertura <= ahora) {
            estadoTxt = `<span class="badge bg-success me-1">🟢 Abierta de inmediato</span> Disponible para presentar desde ahora hasta el <strong>${formatearFechaBonita(cierre)}</strong> (${duracionTxt} en total).`;
        } else {
            const minutosFaltan = Math.round((apertura - ahora) / (1000 * 60));
            const horasFaltan = (minutosFaltan / 60).toFixed(1);
            let tiempoFaltaTxt = minutosFaltan < 60 ? `en ${minutosFaltan} minutos` : `en aprox. ${horasFaltan} horas`;
            estadoTxt = `<span class="badge bg-primary me-1">⏳ Programada</span> Se abrirá automáticamente el <strong>${formatearFechaBonita(apertura)}</strong> (${tiempoFaltaTxt}) y cerrará el <strong>${formatearFechaBonita(cierre)}</strong>.`;
        }

        textoResumen.innerHTML = estadoTxt;
    }

    // Configuración base de Flatpickr elegante en español
    const fpConfigBase = {
        enableTime: true,
        dateFormat: "Y-m-d\\TH:i",
        altInput: true,
        altInputClass: "form-control flatpickr-custom-input border-start-0 ps-1",
        altFormat: "d/m/Y h:i K",
        locale: (typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns.es) ? flatpickr.l10ns.es : "es",
        time_24hr: false,
        minuteIncrement: 5,
        allowInput: false,
        disableMobile: true,
        onChange: () => actualizarResumen()
    };

    let fpApertura = null;
    let fpCierre = null;

    if (typeof flatpickr !== 'undefined') {
        fpApertura = flatpickr("#apertura", {
            ...fpConfigBase,
            defaultDate: inputApertura.value || new Date()
        });

        fpCierre = flatpickr("#cierre", {
            ...fpConfigBase,
            defaultDate: inputCierre.value || new Date(Date.now() + 7 * 24 * 3600 * 1000)
        });
    }

    // Atajos de fecha
    document.querySelectorAll('.btn-atajo-fecha').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const target = btn.dataset.target;
            const opcion = btn.dataset.opcion;
            const ahora = new Date();

            if (target === 'apertura') {
                let d = new Date(ahora);
                if (opcion === 'ahora') {
                    // ahora
                } else if (opcion === 'hoy_tarde') {
                    d.setHours(14, 0, 0, 0);
                    if (d < ahora) d.setDate(d.getDate() + 1);
                } else if (opcion === 'manana_8am') {
                    d.setDate(d.getDate() + 1);
                    d.setHours(8, 0, 0, 0);
                }
                if (fpApertura) {
                    fpApertura.setDate(d, true);
                } else {
                    inputApertura.value = d.toISOString().slice(0, 16);
                    actualizarResumen();
                }
            } else if (target === 'cierre') {
                const baseDate = (fpApertura && fpApertura.selectedDates[0]) 
                    ? fpApertura.selectedDates[0] 
                    : (inputApertura.value ? new Date(inputApertura.value) : new Date());
                let d = new Date(baseDate.getTime());
                if (opcion === '+2h') d.setHours(d.getHours() + 2);
                else if (opcion === '+24h') d.setDate(d.getDate() + 1);
                else if (opcion === '+3d') d.setDate(d.getDate() + 3);
                else if (opcion === '+7d') d.setDate(d.getDate() + 7);

                if (fpCierre) {
                    fpCierre.setDate(d, true);
                } else {
                    inputCierre.value = d.toISOString().slice(0, 16);
                    actualizarResumen();
                }
            }
        });
    });

    if (inputApertura && inputCierre) {
        inputApertura.addEventListener('change', actualizarResumen);
        inputCierre.addEventListener('change', actualizarResumen);
        actualizarResumen();
    }
});
</script>
