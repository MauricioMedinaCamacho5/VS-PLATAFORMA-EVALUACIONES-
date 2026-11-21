'use strict';

(() => {
    const dialogo = document.getElementById('dialogo-confirmacion');
    const titulo = document.getElementById('confirmacion-titulo');
    const mensaje = document.getElementById('confirmacion-mensaje');
    const aceptar = document.getElementById('confirmacion-aceptar');
    const autorizados = new WeakSet();

    window.confirmarAccion = (texto, opciones = {}) => {
        if (!dialogo || typeof dialogo.showModal !== 'function') {
            return Promise.resolve(window.confirm(texto));
        }
        if (dialogo.open) return Promise.resolve(false);

        titulo.textContent = opciones.titulo || 'Confirmar acción';
        mensaje.textContent = texto;
        aceptar.textContent = opciones.aceptar || 'Continuar';
        dialogo.returnValue = '';

        return new Promise(resolve => {
            dialogo.addEventListener('close', () => resolve(dialogo.returnValue === 'confirmar'), {once: true});
            dialogo.showModal();
        });
    };

    document.addEventListener('submit', async event => {
        const formulario = event.target;
        if (!(formulario instanceof HTMLFormElement) || autorizados.has(formulario)) return;
        const boton = event.submitter;
        const texto = boton?.dataset.confirmar || formulario.dataset.confirmar;
        if (!texto) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        if (!await window.confirmarAccion(texto)) return;

        autorizados.add(formulario);
        try {
            if (boton) formulario.requestSubmit(boton);
            else formulario.requestSubmit();
        } finally {
            autorizados.delete(formulario);
        }
    }, true);
})();
