'use strict';

// Editor de preguntas: muestra solo los campos del tipo elegido.
(() => {
    const tipo = document.getElementById('tipo');
    const secciones = document.querySelectorAll('.seccion-tipo');
    function actualizar() {
        for (const seccion of secciones) {
            const visible = seccion.dataset.tipo === tipo.value;
            seccion.hidden = !visible;
            // Los campos ocultos no se envían, para no mezclar datos de otro tipo.
            for (const campo of seccion.querySelectorAll('input, select, textarea')) campo.disabled = !visible;
        }
    }
    if (tipo) {
        tipo.addEventListener('change', actualizar);
        actualizar();
    }
})();
