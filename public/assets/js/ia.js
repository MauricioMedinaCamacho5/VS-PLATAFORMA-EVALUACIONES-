'use strict';

// Módulo de IA: pestañas de fuente, contador de texto, nombre del archivo,
// confirmaciones y animación de carga mientras la IA trabaja.
(() => {
    const MAX_CARACTERES = 20000;

    // ---- Pestañas (patrón ARIA tabs: clic y flechas del teclado) ----
    const pestanas = [...document.querySelectorAll('.ia-tab')];
    const campoFuente = document.getElementById('fuente');
    function activar(pestana, enfocar) {
        for (const otra of pestanas) {
            const activa = otra === pestana;
            otra.classList.toggle('active', activa);
            otra.setAttribute('aria-selected', String(activa));
            otra.tabIndex = activa ? 0 : -1;
            document.getElementById(otra.getAttribute('aria-controls')).hidden = !activa;
        }
        campoFuente.value = pestana.dataset.fuente;
        if (enfocar) pestana.focus();
    }
    for (const pestana of pestanas) {
        pestana.addEventListener('click', () => activar(pestana, false));
        pestana.addEventListener('keydown', event => {
            const indice = pestanas.indexOf(pestana);
            const destino = {ArrowRight: indice + 1, ArrowLeft: indice - 1, Home: 0, End: pestanas.length - 1}[event.key];
            if (destino === undefined) return;
            event.preventDefault();
            activar(pestanas[(destino + pestanas.length) % pestanas.length], true);
        });
    }

    // ---- Contador del texto pegado ----
    const texto = document.getElementById('texto');
    const contador = document.getElementById('contador-texto');
    function contar() {
        const total = texto.value.length;
        contador.textContent = `${total.toLocaleString('es-CO')} caracteres`;
        contador.classList.toggle('text-warning-emphasis', total > MAX_CARACTERES);
        if (total > MAX_CARACTERES) contador.textContent += ' · se recortará';
    }
    if (texto && contador) {
        texto.addEventListener('input', contar);
        contar();
    }

    // ---- Archivo elegido ----
    const archivo = document.getElementById('archivo');
    const nombreArchivo = document.getElementById('archivo-nombre');
    if (archivo && nombreArchivo) {
        archivo.addEventListener('change', () => {
            const elegido = archivo.files[0];
            if (!elegido) {
                nombreArchivo.textContent = 'Elige un archivo PDF o TXT';
                return;
            }
            const megas = (elegido.size / 1048576).toFixed(1);
            nombreArchivo.textContent = elegido.size > 5 * 1048576
                ? `${elegido.name} (${megas} MB): supera el máximo de 5 MB`
                : `${elegido.name} (${megas} MB)`;
        });
    }

    // ---- Confirmaciones y animación de carga ----
    const capa = document.getElementById('ia-carga');
    const mensaje = document.getElementById('ia-carga-mensaje');
    for (const formulario of document.querySelectorAll('form')) {
        formulario.addEventListener('submit', event => {
            const boton = event.submitter;
            if (boton?.dataset.confirmar && !window.confirm(boton.dataset.confirmar)) {
                event.preventDefault();
                return;
            }
            if (!boton?.dataset.carga || !capa) return;
            if (formulario.dataset.enviando === '1') {
                event.preventDefault();
                return;
            }
            formulario.dataset.enviando = '1';
            mensaje.textContent = boton.dataset.carga;
            capa.hidden = false;
            document.body.classList.add('ia-cargando');
            // Desactiva los botones después de enviar (si se desactivan antes, su valor no viaja).
            setTimeout(() => {
                for (const otro of document.querySelectorAll('button:not(:disabled)')) {
                    otro.disabled = true;
                    otro.dataset.desactivadoCarga = '1';
                }
            }, 0);
        });
    }
    // Si el navegador restaura la página desde la caché (botón Atrás), se quita la capa de carga.
    window.addEventListener('pageshow', () => {
        if (!capa) return;
        capa.hidden = true;
        document.body.classList.remove('ia-cargando');
        for (const formulario of document.querySelectorAll('form')) delete formulario.dataset.enviando;
        for (const boton of document.querySelectorAll('[data-desactivado-carga]')) {
            boton.disabled = false;
            delete boton.dataset.desactivadoCarga;
        }
    });
})();
