'use strict';

// Acceso de administración: usa la misma API que la página pública con portal "admin".
let csrf = '';
const status = document.getElementById('status');
const panels = new Set(['admin/dashboard.php', 'docente/dashboard.php', 'estudiante/dashboard.php']);

async function request(action, body) {
    const options = body === undefined ? {} : {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(body)
    };
    const response = await fetch(`../api/index.php?action=${encodeURIComponent(action)}`, options);
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        throw new Error('El servidor no respondió correctamente. Comprueba que Apache y PHP estén activos.');
    }
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Error al procesar la solicitud.');
    return data;
}

async function refresh() {
    const data = await request('session');
    csrf = data.csrf;
    const user = data.usuario;
    if (user && user.rol === 'administrador' && !data.mensaje) {
        window.location.replace('dashboard.php');
        return;
    }
    const otherAccount = !!user && user.rol !== 'administrador';
    document.getElementById('guest').hidden = otherAccount;
    document.getElementById('other-account').hidden = !otherAccount;
    if (otherAccount) {
        document.getElementById('identity').textContent = `${user.nombre} (${user.rol})`;
        if (panels.has(data.panel)) document.getElementById('own-panel').href = `../${data.panel}`;
    }
    return data.mensaje;
}

document.getElementById('login').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    values.portal = 'admin';
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    try {
        await request('login', values);
        form.reset();
        window.location.replace('dashboard.php');
    } catch (error) {
        status.textContent = error.message || 'No se pudo conectar con el servidor.';
    } finally {
        submit.disabled = false;
    }
});

refresh().then(message => {
    status.textContent = message || (new URLSearchParams(window.location.search).has('salida') ? 'Sesión cerrada.' : '');
}).catch(error => {
    status.textContent = error.message || 'No se pudo conectar con el servidor. Comprueba que Apache esté activo.';
});
