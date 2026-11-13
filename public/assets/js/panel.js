'use strict';

// Comportamiento común de los paneles: menú en celular, saludo según la hora y fecha.
(() => {
    const body = document.body;
    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const closeButton = document.getElementById('sidebar-close');
    const backdrop = document.getElementById('sidebar-backdrop');
    const mobile = window.matchMedia('(max-width: 991.98px)');

    function setMenu(open, focusTarget) {
        body.classList.toggle('menu-abierto', open);
        toggle.setAttribute('aria-expanded', String(open));
        // En celular el menú cerrado no debe recibir foco ni leerse.
        if (mobile.matches) sidebar.toggleAttribute('inert', !open);
        if (focusTarget) focusTarget.focus();
    }

    function syncWithViewport() {
        body.classList.remove('menu-abierto');
        toggle.setAttribute('aria-expanded', 'false');
        sidebar.toggleAttribute('inert', mobile.matches);
    }

    if (toggle && sidebar) {
        syncWithViewport();
        mobile.addEventListener('change', syncWithViewport);
        toggle.addEventListener('click', () => {
            const open = !body.classList.contains('menu-abierto');
            setMenu(open, open ? (sidebar.querySelector('.menu-link[href]') || closeButton) : null);
        });
        closeButton?.addEventListener('click', () => setMenu(false, toggle));
        backdrop?.addEventListener('click', () => setMenu(false, toggle));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && body.classList.contains('menu-abierto')) setMenu(false, toggle);
        });
    }

    // Saludo y fecha con la hora del equipo del usuario (el servidor deja un valor inicial).
    const now = new Date();
    const hour = now.getHours();
    const greeting = document.getElementById('greeting');
    if (greeting) greeting.textContent = hour < 12 ? 'Buenos días' : hour < 19 ? 'Buenas tardes' : 'Buenas noches';
    const today = document.getElementById('today');
    if (today) {
        const text = now.toLocaleDateString('es-CO', {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'});
        today.textContent = text.charAt(0).toUpperCase() + text.slice(1);
        const pad = value => String(value).padStart(2, '0');
        today.setAttribute('datetime', `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`);
    }
})();
