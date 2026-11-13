'use strict';

let csrf = '';
let registrationEnabled = false;
const status = document.getElementById('status');
const registerForm = document.getElementById('register');
const registerSubmit = document.getElementById('register-submit');
// Destinos permitidos tras iniciar sesión (estudiante/clases.php: después de unirse con un enlace).
const panels = new Set(['admin/dashboard.php', 'docente/dashboard.php', 'estudiante/dashboard.php', 'estudiante/clases.php']);
const REGISTRO_DESHABILITADO = 'El registro aún no está habilitado: el administrador debe configurar el aviso de privacidad.';

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
    if (!response.ok) {
        const error = new Error(data.error || 'Error al procesar la solicitud.');
        error.errores = data.errores && typeof data.errores === 'object' ? data.errores : null;
        throw error;
    }
    return data;
}

// ---- Validación del registro: un mensaje bajo cada campo ----

const camposRegistro = {
    nombre: () => registerForm.elements.nombre,
    correo: () => registerForm.elements.correo,
    rol: () => registerForm.elements.rol,
    password: () => registerForm.elements.password,
    acepta_privacidad: () => registerForm.elements.acepta_privacidad,
};
let registroIntentado = false;

function validarCampo(nombre) {
    const campo = camposRegistro[nombre]();
    const valor = campo.type === 'checkbox' ? campo.checked : campo.value;
    switch (nombre) {
        case 'nombre':
            if (!valor.trim()) return 'Escribe tu nombre completo.';
            if (valor.trim().length > 150) return 'El nombre admite como máximo 150 caracteres.';
            return '';
        case 'correo':
            if (!valor.trim()) return 'Escribe tu correo electrónico.';
            if (campo.validity.typeMismatch || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor.trim())) {
                return 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
            }
            return '';
        case 'rol':
            return valor === 'estudiante' || valor === 'docente' ? '' : 'Selecciona si eres estudiante o docente.';
        case 'password': {
            if (!valor) return 'Escribe una contraseña.';
            const caracteres = [...valor].length;
            if (caracteres < 8) return `La contraseña debe tener al menos 8 caracteres (llevas ${caracteres}).`;
            if (new TextEncoder().encode(valor).length > 72) return 'La contraseña es demasiado larga (máximo 72 bytes).';
            return '';
        }
        case 'acepta_privacidad':
            return valor ? '' : 'Debes aceptar el aviso de tratamiento de datos.';
    }
    return '';
}

function mostrarError(nombre, mensaje) {
    const contenedor = document.getElementById(`error-${nombre}`);
    contenedor.textContent = mensaje;
    contenedor.hidden = !mensaje;
    const campo = camposRegistro[nombre]?.();
    if (campo) {
        if (mensaje) campo.setAttribute('aria-invalid', 'true');
        else campo.removeAttribute('aria-invalid');
    }
}

function mostrarErrorFormulario(mensaje) {
    const contenedor = document.getElementById('error-formulario');
    contenedor.textContent = mensaje;
    contenedor.hidden = !mensaje;
}

function validarRegistro() {
    const errores = {};
    for (const nombre of Object.keys(camposRegistro)) {
        const mensaje = validarCampo(nombre);
        mostrarError(nombre, mensaje);
        if (mensaje) errores[nombre] = mensaje;
    }
    return errores;
}

function limpiarErroresRegistro() {
    registroIntentado = false;
    for (const nombre of Object.keys(camposRegistro)) mostrarError(nombre, '');
    mostrarErrorFormulario('');
}

// Tras el primer intento, cada campo se revalida mientras el usuario lo corrige.
for (const nombre of Object.keys(camposRegistro)) {
    const campo = camposRegistro[nombre]();
    const evento = campo.tagName === 'SELECT' || campo.type === 'checkbox' ? 'change' : 'input';
    campo.addEventListener(evento, () => {
        if (registroIntentado) mostrarError(nombre, validarCampo(nombre));
    });
    campo.addEventListener('blur', () => {
        if (registroIntentado || campo.value) mostrarError(nombre, validarCampo(nombre));
    });
}

registerForm.addEventListener('submit', async event => {
    event.preventDefault();
    registroIntentado = true;
    status.textContent = '';
    mostrarErrorFormulario('');
    const errores = validarRegistro();
    const primero = Object.keys(errores)[0];
    if (primero) {
        camposRegistro[primero]().focus();
        return;
    }
    if (!registrationEnabled) {
        mostrarErrorFormulario(REGISTRO_DESHABILITADO);
        return;
    }
    const values = Object.fromEntries(new FormData(registerForm));
    values.acepta_privacidad = registerForm.elements.acepta_privacidad.checked;
    registerSubmit.disabled = true;
    try {
        const result = await request('register', values);
        registerForm.reset();
        openDashboard(result.panel);
    } catch (error) {
        if (error.errores) {
            for (const [nombre, mensaje] of Object.entries(error.errores)) {
                if (nombre in camposRegistro) mostrarError(nombre, String(mensaje));
            }
            const primeroServidor = Object.keys(error.errores).find(nombre => nombre in camposRegistro);
            if (primeroServidor) camposRegistro[primeroServidor]().focus();
            else mostrarErrorFormulario(error.message);
        } else {
            mostrarErrorFormulario(error.message || 'No se pudo conectar con el servidor.');
        }
    } finally {
        registerSubmit.disabled = false;
    }
});

// ---- Pestañas y sesión ----

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
    document.getElementById('privacy').textContent = notice || REGISTRO_DESHABILITADO;
    registrationEnabled = !!notice;
    if (!data.usuario && data.union_pendiente) {
        const {clase, docente, codigo} = data.union_pendiente;
        return `Inicia sesión o crea tu cuenta de estudiante para unirte a «${clase}» (${docente}, código ${codigo}).`;
    }
    return data.mensaje;
}

for (const [id, panel] of [['show-login', 'login'], ['login-link', 'login'], ['show-register', 'register'], ['register-link', 'register']]) {
    document.getElementById(id).addEventListener('click', () => {
        showPanel(panel);
        status.textContent = '';
        limpiarErroresRegistro();
    });
}

document.getElementById('login').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    try {
        const result = await request('login', values);
        form.reset();
        openDashboard(result.panel);
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
