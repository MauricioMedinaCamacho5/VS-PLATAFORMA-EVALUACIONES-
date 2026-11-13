'use strict';

// Examen: temporizador, autoguardado cada 30 s y envío automático al acabarse el tiempo.
(() => {
    const form = document.getElementById('examen');
    if (!form) return;

    const AUTOGUARDADO_MS = 30000;
    const reloj = document.getElementById('temporizador');
    const caja = document.getElementById('temporizador-caja');
    const estado = document.getElementById('estado-guardado');
    const avisoTiempo = document.getElementById('aviso-tiempo');
    const contador = document.getElementById('contador-respondidas');
    const barra = document.getElementById('barra-progreso');
    const total = Number(form.dataset.total) || 0;
    // Hora límite según el reloj del servidor, convertida al reloj local una sola vez.
    const limite = Date.now() + Number(form.dataset.segundos) * 1000;
    let enviando = false;
    let cambiosSinGuardar = false;
    const avisados = new Set();

    function respuestas() {
        const datos = {};
        for (const [nombre, valor] of new FormData(form)) {
            const coincidencia = /^respuestas\[(\d+)\]$/.exec(nombre);
            if (coincidencia) datos[coincidencia[1]] = valor;
        }
        return datos;
    }

    function actualizarProgreso() {
        const respondidas = Object.values(respuestas()).filter(valor => String(valor).trim() !== '').length;
        contador.textContent = respondidas;
        barra.style.width = total ? `${Math.round(respondidas / total * 100)}%` : '0%';
    }

    function mostrarEstado(texto, icono) {
        estado.innerHTML = '';
        const i = document.createElement('i');
        i.className = `bi ${icono} me-1`;
        i.setAttribute('aria-hidden', 'true');
        estado.append(i, document.createTextNode(texto));
    }

    function enviar(automatico) {
        if (enviando) return;
        enviando = true;
        document.getElementById('automatico').value = automatico ? '1' : '0';
        window.removeEventListener('beforeunload', avisarSalida);
        form.submit();
    }

    async function guardar() {
        if (enviando || !cambiosSinGuardar) return;
        cambiosSinGuardar = false;
        mostrarEstado('Guardando…', 'bi-arrow-repeat');
        try {
            const respuesta = await fetch('examen_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': form.dataset.csrf},
                body: JSON.stringify({intento: Number(form.dataset.intento), respuestas: respuestas()}),
            });
            const datos = await respuesta.json().catch(() => ({}));
            if (datos.vencido || datos.enviado) {
                enviar(true);
                return;
            }
            if (!respuesta.ok) throw new Error(datos.error || 'error');
            const hora = new Date().toLocaleTimeString('es-CO', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            mostrarEstado(`Guardado a las ${hora}`, 'bi-cloud-check');
        } catch {
            cambiosSinGuardar = true;
            mostrarEstado('Sin conexión: se reintentará', 'bi-cloud-slash');
        }
    }

    function tic() {
        const restante = Math.max(0, Math.round((limite - Date.now()) / 1000));
        const minutos = Math.floor(restante / 60);
        reloj.textContent = `${String(minutos).padStart(2, '0')}:${String(restante % 60).padStart(2, '0')}`;
        caja.classList.toggle('poco-tiempo', restante <= 60);
        for (const marca of [300, 60]) {
            if (restante <= marca && restante > 0 && !avisados.has(marca)) {
                avisados.add(marca);
                avisoTiempo.textContent = marca === 300 ? 'Quedan 5 minutos.' : 'Queda 1 minuto. La evaluación se enviará sola.';
            }
        }
        if (restante === 0) {
            mostrarEstado('Tiempo agotado: enviando…', 'bi-send');
            enviar(true);
            return;
        }
        setTimeout(tic, 1000 - (Date.now() % 1000) + 5);
    }

    function avisarSalida(event) {
        if (!enviando) {
            event.preventDefault();
            event.returnValue = '';
        }
    }

    form.addEventListener('change', () => { cambiosSinGuardar = true; actualizarProgreso(); });
    form.addEventListener('input', () => { cambiosSinGuardar = true; actualizarProgreso(); });
    form.addEventListener('submit', event => {
        event.preventDefault();
        const respondidas = Number(contador.textContent);
        const pendientes = total - respondidas;
        const pregunta = pendientes > 0
            ? `Tienes ${pendientes} pregunta${pendientes === 1 ? '' : 's'} sin responder. ¿Enviar de todas formas?`
            : '¿Enviar la evaluación? Ya no podrás cambiar tus respuestas.';
        if (window.confirm(pregunta)) enviar(false);
    });
    window.addEventListener('beforeunload', avisarSalida);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') guardar(); });

    setInterval(guardar, AUTOGUARDADO_MS);
    tic();
})();
