'use strict';

// Copiar código/enlace de una clase y confirmar acciones delicadas (formularios con data-confirmar).
(() => {
    const aviso = document.getElementById('copiado-aviso');

    async function copiar(texto) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(texto);
            return;
        }
        // Respaldo para http en red local (fuera de localhost), donde el portapapeles moderno no está disponible.
        const campo = document.createElement('textarea');
        campo.value = texto;
        campo.setAttribute('readonly', '');
        campo.style.position = 'fixed';
        campo.style.opacity = '0';
        document.body.append(campo);
        campo.select();
        const ok = document.execCommand('copy');
        campo.remove();
        if (!ok) throw new Error('copy');
    }

    for (const boton of document.querySelectorAll('[data-copiar]')) {
        const etiqueta = boton.querySelector('span');
        const original = etiqueta ? etiqueta.textContent : '';
        boton.addEventListener('click', async () => {
            try {
                await copiar(boton.dataset.copiar);
                if (etiqueta) etiqueta.textContent = '¡Copiado!';
                if (aviso) aviso.textContent = 'Copiado al portapapeles.';
            } catch {
                if (etiqueta) etiqueta.textContent = 'Selecciona y copia';
            }
            setTimeout(() => { if (etiqueta) etiqueta.textContent = original; }, 2000);
        });
    }

    for (const formulario of document.querySelectorAll('form[data-confirmar]')) {
        formulario.addEventListener('submit', event => {
            if (!window.confirm(formulario.dataset.confirmar)) event.preventDefault();
        });
    }
})();
