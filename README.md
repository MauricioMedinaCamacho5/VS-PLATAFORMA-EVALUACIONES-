# Plataforma de evaluaciones: base inicial

**Estado: implementación parcial; no apta para producción.** Este repositorio
contiene el esquema inicial y un primer módulo de autenticación. No contiene aún
la plataforma completa solicitada.

## Contenido

```text
evaluaciones/
  .htaccess                 Deniega el acceso web a la raíz del proyecto
  app/bootstrap.php         Configuración, PDO, sesión y CSRF
  app/dashboard.php         Plantilla compartida de los paneles
  includes/auth.php         Control de acceso por rol
  config.example.php        Plantilla de configuración
  database/evaluaciones.sql  Esquema y cuentas de prueba
  public/
    .htaccess
    index.html              Interfaz HTML de acceso y registro
    index.php               Redirección de compatibilidad a index.html
    api/index.php           API JSON de sesión y autenticación
    admin/dashboard.php     Panel del administrador
    admin/usuarios.php      Consulta y cambio de roles
    docente/dashboard.php   Panel del docente
    estudiante/dashboard.php Panel del estudiante
    logout.php              Cierre de sesión mediante POST con CSRF
    assets/                 CSS y JavaScript del módulo inicial
```

La raíz pública es exclusivamente `public/`. La denegación mediante `.htaccess`
requiere que Apache tenga habilitado `AllowOverride All`; para mayor seguridad
se recomienda configurar un VirtualHost cuyo `DocumentRoot` sea esa carpeta.
Comprueba siempre que Apache rechaza el acceso directo a `app/bootstrap.php`,
`config.example.php` y `database/evaluaciones.sql` antes de usar el sistema.
El directorio `public` habilita expresamente el acceso heredado de la raíz.

## Instalación inicial en XAMPP

1. Copia el proyecto a `C:\xamppp\htdocs\evaluaciones` (ajusta la ruta si tu
   XAMPP está instalado en otro lugar). Inicia Apache y MySQL desde XAMPP.
2. En phpMyAdmin importa `database/evaluaciones.sql` en una instalación vacía.
   El script crea `evaluaciones_db` y sus tablas. No lo importes sobre datos
   existentes: no es una migración.
3. Copia `config.example.php` a
   `C:\xamppp\evaluaciones-config\config.php`, **fuera de htdocs**, y ajusta
   usuario y contraseña de MySQL. Para otra ubicación define la variable de
   entorno del proceso Apache `EVALUACIONES_CONFIG` con la ruta absoluta.
4. Define `privacy_notice` con un aviso real aprobado por el responsable del
   tratamiento. Si está vacío, el registro permanece deshabilitado. La
   aplicación registra el texto aceptado y la fecha; esto no acredita por sí
   solo el cumplimiento de la Ley 1581 de 2012.
5. Abre `http://localhost/evaluaciones/public/index.html`. Si utilizas un
   VirtualHost con `public/` como raíz, abre la dirección configurada para ese
   VirtualHost. No abras el HTML con doble clic (`file://`): la página necesita
   Apache y PHP para comunicarse con MySQL.

El SQL crea un administrador, dos docentes y cinco estudiantes con direcciones
`admin@example.test`, `docente1@example.test`, `docente2@example.test` y
`estudiante1@example.test` a `estudiante5@example.test`. Todos usan la
contraseña **SoloPrueba-2026!**. Son datos exclusivamente locales: elimínalos
o cambia sus contraseñas antes de cualquier uso real. Las cuentas de prueba no
representan una aceptación real del aviso.

## Comprobaciones del módulo inicial

- Inicia sesión con cada una de las cuentas de prueba y verifica que se abre
  el panel del rol correspondiente y muestra su nombre. Cierra la sesión.
  Las credenciales incorrectas y cuentas desactivadas deben rechazarse.
- Con sesión iniciada, abre `index.html` y verifica la redirección al panel.
  Intenta abrir el panel de otro rol: debe mostrar un mensaje de acceso
  denegado y un enlace al panel correcto. Sin sesión debe pedir iniciar sesión.
- Con un aviso configurado, registra por separado un estudiante y un docente
  con contraseñas de al menos 8 caracteres y acepta el aviso. Cada registro
  inicia sesión y abre el panel correspondiente. El correo duplicado debe
  rechazarse. El servidor rechaza administrador o cualquier otro rol enviado
  al registro público.
- Con el aviso vacío, verifica que no se puede registrar ninguna cuenta.
- Comprueba que una solicitud POST sin la cabecera CSRF se rechaza. Cambiar
  el rol o desactivar al usuario en la base de datos se refleja al consultar
  la sesión de nuevo.
- Intenta abrir los archivos privados por HTTP y confirma que Apache responde
  con acceso denegado. Si no lo hace, no expongas el proyecto: corrige la
  configuración de Apache o usa un VirtualHost con raíz en `public/`.

## Cambiar una cuenta existente a administrador

En phpMyAdmin, selecciona `evaluaciones_db` y ejecuta:

```sql
UPDATE usuarios
SET rol_id = (SELECT id FROM roles WHERE nombre = 'administrador')
WHERE correo = 'mauriciomedinacamacho5@gmail.com';
```

La cuenta debe existir y estar activa. Revisa las filas afectadas; si es cero,
comprueba el correo. El cambio de rol se refleja al siguiente acceso a una
página protegida.

Para cambiar otros roles, entra con una cuenta administradora y abre **Gestionar
roles**. Selecciona estudiante, docente o administrador y pulsa **Guardar
rol**. El registro público permite escoger estudiante o docente, pero no
administrador; los cambios de rol se
aplican también a sesiones ya iniciadas al efectuar la siguiente solicitud.
La aplicación impide quitar el rol al último administrador activo. Si un
administrador cambia su propio rol, deberá continuar en el panel del nuevo rol.
Solo el administrador puede acceder a la página y modificar roles; los cambios
requieren un token CSRF válido.

**Importante:** el registro público de docentes no verifica su identidad ni
requiere aprobación. Cualquier visitante puede obtener ese rol; no lo expongas
a usuarios reales hasta implementar un proceso de validación apropiado.

## Pendiente

Faltan creación, edición y desactivación de usuarios desde administración,
gestión de grupos, recuperación con PHPMailer,
evaluaciones, preguntas y banco, generación y revisión de IA, intentos,
calificación y resultados, exportación, auditoría operativa y PWA. Los paneles
usan Bootstrap 5 desde CDN y cuentan con estilos básicos si no hay conexión.
Las tablas son una base inicial, no una garantía de que esos flujos funcionen.
También faltan protección contra fuerza bruta, pruebas integrales, revisión
legal y las mediciones de rendimiento y concurrencia solicitadas.
No se ha demostrado el cumplimiento de WCAG ni los umbrales de 3/5/30 segundos
o de 200 estudiantes simultáneos.

Una PWA en un celular distinto del servidor necesita HTTPS con un certificado
confiable; HTTP de una IP de red local no es un contexto seguro para instalarla.
