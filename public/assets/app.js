'use strict';

let csrf = '';
let registrationEnabled = false;
const status = document.getElementById('status');
const registerSubmit = document.getElementById('register-submit');
const panels = new Set(['admin/dashboard.php', 'docente/dashboard.php', 'estudiante/dashboard.php']);

function openDashboard(panel) {
    if (!panels.has(panel)) throw new Error('Tu cuenta no tiene un panel válido. Contacta al administrador.');
    window.location.replace(panel);
}

async function request(action, body) {
    const options = body === undefined ? {} : {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(body)
    };
    const response = await fetch(`api/index.php?action=${encodeURIComponent(action)}`, options);
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        throw new Error('El servidor no respondió correctamente. Comprueba que Apache y PHP estén activos.');
    }
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Error al procesar la solicitud.');
    return data;
}

function showPanel(panel) {
    const login = panel === 'login';
    document.getElementById('login-panel').hidden = !login;
    document.getElementById('register-panel').hidden = login;
    for (const [id, active] of [['show-login', login], ['show-register', !login]]) {
        const button = document.getElementById(id);
        button.classList.toggle('active', active);
        if (active) button.setAttribute('aria-current', 'page');
        else button.removeAttribute('aria-current');
    }
}

async function refresh() {
    const data = await request('session');
    csrf = data.csrf;
    if (data.usuario && !data.mensaje) {
        openDashboard(data.panel);
        return;
    }
    document.getElementById('guest').hidden = !!data.usuario;
    document.getElementById('authenticated').hidden = !data.usuario;
    document.getElementById('identity').textContent = data.usuario ? `${data.usuario.nombre} (${data.usuario.rol})` : '';
    if (data.usuario) {
        if (!panels.has(data.panel)) throw new Error('Tu cuenta no tiene un panel válido. Contacta al administrador.');
        document.getElementById('dashboard-link').href = data.panel;
    }
    const notice = typeof data.aviso_privacidad === 'string' ? data.aviso_privacidad.trim() : '';
    document.getElementById('privacy').textContent = notice || 'El registro no está habilitado: falta configurar el aviso de privacidad.';
    registrationEnabled = !!notice;
    registerSubmit.disabled = !registrationEnabled;
    return data.mensaje;
}

for (const [id, panel] of [['show-login', 'login'], ['login-link', 'login'], ['show-register', 'register'], ['register-link', 'register']]) {
    document.getElementById(id).addEventListener('click', () => {
        showPanel(panel);
        status.textContent = '';
    });
}

for (const action of ['login', 'register']) {
    document.getElementById(action).addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const values = Object.fromEntries(new FormData(form));
        if (action === 'register') values.acepta_privacidad = form.elements.acepta_privacidad.checked;
        const submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
            const result = await request(action, values);
            form.reset();
            openDashboard(result.panel);
        } catch (error) {
            status.textContent = error.message || 'No se pudo conectar con el servidor.';
        } finally {
            submit.disabled = action === 'register' && !registrationEnabled;
        }
    });
}

refresh().then(message => {
    status.textContent = message || (new URLSearchParams(window.location.search).has('salida') ? 'Sesión cerrada.' : '');
}).catch(error => {
    status.textContent = error.message || 'No se pudo conectar con el servidor. Comprueba que Apache esté activo.';
});
