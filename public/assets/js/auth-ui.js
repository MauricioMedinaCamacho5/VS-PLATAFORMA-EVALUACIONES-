'use strict';

// Botón de ojo para mostrar u ocultar la contraseña (login, registro y acceso de administración).
for (const button of document.querySelectorAll('.toggle-password')) {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) continue;
    const icon = button.querySelector('i');
    const update = visible => {
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        icon?.classList.toggle('bi-eye', !visible);
        icon?.classList.toggle('bi-eye-slash', visible);
    };
    button.addEventListener('click', () => {
        update(input.type === 'password');
        input.focus();
    });
    // Al enviar o limpiar el formulario la contraseña vuelve a ocultarse.
    input.form?.addEventListener('submit', () => update(false));
    input.form?.addEventListener('reset', () => update(false));
}
