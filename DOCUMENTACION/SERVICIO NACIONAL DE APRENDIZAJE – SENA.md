### SERVICIO NACIONAL DE APRENDIZAJE – SENA

# PLATAFORMA DE EVALUACIONES EN LÍNEA

## Especificación de Requisitos de Software (SRS)

*Requisitos funcionales y no funcionales · Historias de usuario · Casos de uso*

---

**Presentado por**

Mauricio Medina Camacho

**Ficha**

3413988

**Presentado a**

Instructor Javier Pinto

---

**Servicio Nacional de Aprendizaje – SENA**

Noviembre de 2026

---

## Tabla de contenido

1. [Introducción](#mv3324qdt80.85)
2. [Requisitos funcionales](#mv3324qdt80.5174)
   - [2.1 Autenticación y usuarios](#mv3324qdt80.5207)
   - [2.2 Módulo de evaluaciones](#mv3324qdt80.7246)
   - [2.3 Módulo de preguntas](#mv3324qdt80.10091)
   - [2.4 Generación de preguntas con IA](#mv3324qdt80.12293)
   - [2.5 Módulo de intentos](#mv3324qdt80.14652)
   - [2.6 Módulo de resultados](#mv3324qdt80.17313)
3. [Requisitos no funcionales](#mv3324qdt80.19998)
4. [Historias de usuario](#mv3324qdt80.26864)
5. [Casos de uso](#mv3324qdt80.43098)

---

## 1. Introducción

**Propósito.** Este documento describe los requisitos de una plataforma web para crear evaluaciones, gestionar preguntas, registrar intentos de los estudiantes y calificar automáticamente, con apoyo de inteligencia artificial para generar preguntas a partir de contenidos suministrados.

**Alcance.** El sistema permitirá a docentes crear y publicar evaluaciones, construir bancos de preguntas manualmente o con IA, y consultar resultados. Los estudiantes podrán presentar evaluaciones en línea y conocer su calificación.

**Tecnología.** Frontend en React; backend con Firebase (Authentication, Cloud Firestore, Cloud Functions, Storage y Hosting); integración con un modelo de lenguaje vía API.

**Actores.**

- Administrador: gestiona usuarios y roles.
- Docente: crea evaluaciones, preguntas y consulta resultados.
- Estudiante: presenta evaluaciones y consulta sus resultados.
- Servicio de IA (actor externo): genera preguntas a partir de contenido.

## 2. Requisitos funcionales

### 2.1 Autenticación y usuarios

| Identificador: RF-01 | Nombre: Registro e inicio de sesión |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Base de RF-02 y RF-04 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de inicio de sesión y registro |
| **Entrada:** Correo; contraseña | **Salida:** Sesión iniciada |
| **Descripción** | El sistema debe permitir el registro e inicio de sesión con correo y contraseña. |
| **Manejo de situaciones anormales** | Credenciales inválidas; correo ya registrado. |

| Identificador: RF-02 | Nombre: Gestión de roles |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-01 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Panel de administración |
| **Entrada:** Usuario; rol | **Salida:** Permisos aplicados |
| **Descripción** | El sistema debe asignar roles: administrador, docente y estudiante. |
| **Manejo de situaciones anormales** | Rol inexistente; usuario sin rol asignado. |

| Identificador: RF-03 | Nombre: Administración de usuarios |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-02 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Panel de administración de usuarios |
| **Entrada:** Datos del usuario; acción a realizar | **Salida:** Usuario creado, editado o desactivado |
| **Descripción** | El administrador debe poder crear, editar, desactivar usuarios y cambiar su rol. |
| **Manejo de situaciones anormales** | Usuario no encontrado; intento de desactivar al único administrador. |

| Identificador: RF-04 | Nombre: Recuperación de contraseña |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-01 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Pantalla de recuperación de contraseña |
| **Entrada:** Correo | **Salida:** Correo de restablecimiento enviado |
| **Descripción** | El sistema debe permitir recuperar la contraseña por correo. |
| **Manejo de situaciones anormales** | Correo no registrado; falla del servicio de correo. |

### 2.2 Módulo de evaluaciones

| Identificador: RF-05 | Nombre: Creación de evaluaciones |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-02 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Formulario de evaluación |
| **Entrada:** Título; descripción; tiempo límite; fechas de apertura y cierre; intentos permitidos; nota mínima | **Salida:** Evaluación creada en borrador |
| **Descripción** | El docente debe poder crear una evaluación con título, descripción, tiempo límite, fecha de apertura y cierre, número de intentos permitidos y nota mínima de aprobación. |
| **Manejo de situaciones anormales** | Campos obligatorios vacíos; fecha de cierre anterior a la de apertura. |

| Identificador: RF-06 | Nombre: Edición, duplicación y eliminación de evaluaciones |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-05 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Listado de evaluaciones |
| **Entrada:** Evaluación; acción a realizar | **Salida:** Evaluación editada, duplicada o eliminada |
| **Descripción** | El docente debe poder editar, duplicar y eliminar sus evaluaciones. |
| **Manejo de situaciones anormales** | Evaluación con intentos registrados que no se puede eliminar; usuario sin permisos. |

| Identificador: RF-07 | Nombre: Publicación de evaluaciones |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-05 y RF-10 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Listado de evaluaciones |
| **Entrada:** Evaluación; estado (publicada / no publicada) | **Salida:** Evaluación visible u oculta para los estudiantes |
| **Descripción** | El docente debe poder publicar o despublicar una evaluación. |
| **Manejo de situaciones anormales** | Evaluación sin preguntas; fecha de cierre vencida. |

| Identificador: RF-08 | Nombre: Asignación a grupos |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-05 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Formulario de asignación |
| **Entrada:** Evaluación; grupo o lista de estudiantes | **Salida:** Evaluación asignada |
| **Descripción** | El docente debe poder asignar una evaluación a un grupo de estudiantes. |
| **Manejo de situaciones anormales** | Grupo vacío; estudiante inactivo. |

| Identificador: RF-09 | Nombre: Orden aleatorio |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-05 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Baja | **Documentos de visualización asociados:** Configuración de la evaluación |
| **Entrada:** Opción de aleatorización activada | **Salida:** Preguntas y opciones en orden aleatorio en cada intento |
| **Descripción** | El sistema debe permitir mostrar las preguntas y opciones en orden aleatorio. |
| **Manejo de situaciones anormales** | Evaluación con una sola pregunta o una sola opción. |

### 2.3 Módulo de preguntas

| Identificador: RF-10 | Nombre: Creación de preguntas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-05 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Editor de preguntas |
| **Entrada:** Tipo de pregunta; enunciado; opciones; respuesta correcta; puntaje | **Salida:** Pregunta guardada en la evaluación |
| **Descripción** | El docente debe poder crear preguntas de selección múltiple, verdadero/falso y respuesta corta. |
| **Manejo de situaciones anormales** | Pregunta sin respuesta correcta marcada; puntaje inválido. |

| Identificador: RF-11 | Nombre: Estructura de la pregunta |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-10 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Editor de preguntas |
| **Entrada:** Enunciado; opciones; respuesta correcta; puntaje; retroalimentación | **Salida:** Pregunta completa almacenada |
| **Descripción** | Cada pregunta debe tener enunciado, opciones, respuesta correcta, puntaje y retroalimentación opcional. |
| **Manejo de situaciones anormales** | Opciones duplicadas; menos de dos opciones en selección múltiple. |

| Identificador: RF-12 | Nombre: Edición, eliminación y reordenamiento de preguntas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-10 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Editor de preguntas |
| **Entrada:** Pregunta; acción; nueva posición | **Salida:** Lista de preguntas actualizada |
| **Descripción** | El docente debe poder editar, eliminar y reordenar preguntas. |
| **Manejo de situaciones anormales** | Pregunta usada en intentos ya calificados. |

| Identificador: RF-13 | Nombre: Banco de preguntas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-10 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Baja | **Documentos de visualización asociados:** Banco de preguntas |
| **Entrada:** Criterio de búsqueda; preguntas seleccionadas | **Salida:** Preguntas agregadas a la evaluación |
| **Descripción** | El docente debe poder reutilizar preguntas desde un banco de preguntas. |
| **Manejo de situaciones anormales** | Banco vacío; pregunta ya agregada a la evaluación. |

### 2.4 Generación de preguntas con IA

| Identificador: RF-14 | Nombre: Carga de contenido base |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Utilizado por RF-16 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Formulario de generación con IA |
| **Entrada:** Texto o archivo PDF / TXT | **Salida:** Contenido listo para procesar |
| **Descripción** | El docente debe poder ingresar texto o subir un archivo (PDF o TXT) como contenido base. |
| **Manejo de situaciones anormales** | Formato de archivo no soportado; archivo mayor al límite; PDF sin texto legible. |

| Identificador: RF-15 | Nombre: Parámetros de generación |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-14 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Formulario de generación con IA |
| **Entrada:** Cantidad; tipo de pregunta; nivel de dificultad | **Salida:** Solicitud de generación configurada |
| **Descripción** | El docente debe poder indicar cantidad, tipo y nivel de dificultad de las preguntas a generar. |
| **Manejo de situaciones anormales** | Cantidad fuera del rango permitido. |

| Identificador: RF-16 | Nombre: Generación de preguntas con IA |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-14 y RF-15 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de preguntas generadas |
| **Entrada:** Contenido base; parámetros de generación | **Salida:** Preguntas en formato estructurado (JSON) |
| **Descripción** | El sistema debe enviar el contenido al servicio de IA y recibir las preguntas en formato estructurado. |
| **Manejo de situaciones anormales** | Servicio de IA no disponible; tiempo de espera agotado; respuesta con formato inválido. |

| Identificador: RF-17 | Nombre: Revisión de preguntas generadas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-16 y RF-10 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de revisión de preguntas |
| **Entrada:** Preguntas generadas; decisión del docente | **Salida:** Preguntas aprobadas guardadas en la evaluación |
| **Descripción** | El docente debe revisar, editar, aprobar o descartar cada pregunta generada antes de guardarla. |
| **Manejo de situaciones anormales** | El docente descarta todas las preguntas; pregunta generada sin respuesta correcta. |

### 2.5 Módulo de intentos

| Identificador: RF-18 | Nombre: Consulta de evaluaciones disponibles |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-07 y RF-08 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Panel del estudiante |
| **Entrada:** Usuario autenticado | **Salida:** Lista de evaluaciones disponibles |
| **Descripción** | El estudiante debe ver las evaluaciones disponibles para él. |
| **Manejo de situaciones anormales** | Estudiante sin evaluaciones asignadas. |

| Identificador: RF-19 | Nombre: Registro del intento |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-18 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de presentación de la evaluación |
| **Entrada:** Respuestas del estudiante | **Salida:** Intento registrado con hora de inicio y envío |
| **Descripción** | El sistema debe registrar el inicio del intento, las respuestas y la hora de envío. |
| **Manejo de situaciones anormales** | Pérdida de conexión; cierre del navegador. |

| Identificador: RF-20 | Nombre: Temporizador y envío automático |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-19 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de presentación de la evaluación |
| **Entrada:** Tiempo límite; hora de inicio | **Salida:** Intento enviado al agotarse el tiempo |
| **Descripción** | El sistema debe mostrar un temporizador y enviar automáticamente el intento al agotarse el tiempo. |
| **Manejo de situaciones anormales** | Hora del dispositivo incorrecta; sin conexión al vencer el tiempo. |

| Identificador: RF-21 | Nombre: Guardado parcial de respuestas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-19 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de presentación de la evaluación |
| **Entrada:** Respuestas parciales | **Salida:** Progreso guardado |
| **Descripción** | El sistema debe guardar las respuestas parcialmente para no perderlas ante fallas de conexión. |
| **Manejo de situaciones anormales** | Falla de red durante el guardado. |

| Identificador: RF-22 | Nombre: Control de intentos y fechas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-05 y RF-19 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Panel del estudiante |
| **Entrada:** Usuario; evaluación | **Salida:** Acceso permitido o denegado |
| **Descripción** | El sistema debe impedir iniciar más intentos que los permitidos o fuera del rango de fechas. |
| **Manejo de situaciones anormales** | Intentos agotados; evaluación cerrada o aún no abierta. |

### 2.6 Módulo de resultados

| Identificador: RF-23 | Nombre: Calificación automática |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-19 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de resultado |
| **Entrada:** Intento enviado | **Salida:** Nota calculada y guardada |
| **Descripción** | El sistema debe calificar automáticamente las preguntas cerradas al enviar el intento. |
| **Manejo de situaciones anormales** | Error en el cálculo; respuestas cortas pendientes de revisión manual. |

| Identificador: RF-24 | Nombre: Consulta de resultado del estudiante |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-23 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Pantalla de resultado |
| **Entrada:** Intento calificado | **Salida:** Nota y retroalimentación por pregunta |
| **Descripción** | El estudiante debe poder ver su nota y, si el docente lo habilita, la retroalimentación por pregunta. |
| **Manejo de situaciones anormales** | Retroalimentación deshabilitada; calificación aún pendiente. |

| Identificador: RF-25 | Nombre: Resultados y estadísticas del docente |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-23 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Panel de resultados |
| **Entrada:** Evaluación | **Salida:** Resultados por estudiante; promedio; tasa de aprobación; preguntas más falladas |
| **Descripción** | El docente debe ver los resultados por estudiante y estadísticas: promedio, tasa de aprobación y preguntas más falladas. |
| **Manejo de situaciones anormales** | Evaluación sin intentos registrados. |

| Identificador: RF-26 | Nombre: Exportación de resultados |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Depende de RF-25 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Baja | **Documentos de visualización asociados:** Panel de resultados |
| **Entrada:** Evaluación; formato (CSV o Excel) | **Salida:** Archivo descargado |
| **Descripción** | El docente debe poder exportar los resultados a CSV o Excel. |
| **Manejo de situaciones anormales** | Sin datos para exportar; error al generar el archivo. |

| Identificador: RF-27 | Nombre: Calificación manual de respuestas cortas |
| --- | --- |
| **Tipo:** Funcional | **Requerimiento que lo utiliza o especializa:** Especializa RF-23 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Pantalla de revisión manual |
| **Entrada:** Respuesta del estudiante; nueva calificación | **Salida:** Nota actualizada |
| **Descripción** | El docente debe poder revisar y ajustar manualmente la calificación de respuestas cortas. |
| **Manejo de situaciones anormales** | Puntaje mayor al máximo de la pregunta. |

## 3. Requisitos no funcionales

| Identificador: RNF-01 | Nombre: Procesamiento seguro en servidor |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-16 y RF-23 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Cloud Functions |
| **Entrada:** Intento enviado; solicitud de generación | **Salida:** Calificación y preguntas procesadas en servidor |
| **Descripción** | La calificación y la llamada a la IA deben ejecutarse en Cloud Functions; las respuestas correctas nunca se envían al navegador del estudiante. |
| **Manejo de situaciones anormales** | Falla de la función; intento de manipulación desde el navegador. |

| Identificador: RNF-02 | Nombre: Control de acceso por rol |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a toda la plataforma · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Reglas de seguridad de Firestore |
| **Entrada:** Solicitudes de lectura y escritura | **Salida:** Acceso permitido o denegado |
| **Descripción** | Las reglas de Firestore deben restringir el acceso según el rol; un estudiante solo ve sus propios intentos y resultados. |
| **Manejo de situaciones anormales** | Acceso no autorizado; sesión expirada. |

| Identificador: RNF-03 | Nombre: Protección de la clave de IA |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-16 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Configuración de secretos del servidor |
| **Entrada:** Solicitud al servicio de IA | **Salida:** Llamada autenticada sin exponer la clave |
| **Descripción** | La clave de la API de IA debe guardarse como secreto del servidor. |
| **Manejo de situaciones anormales** | Clave inválida o expirada; exposición accidental de la clave. |

| Identificador: RNF-04 | Nombre: Tiempo de respuesta |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a toda la plataforma · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Todas las pantallas |
| **Entrada:** Solicitudes del usuario | **Salida:** Página cargada en menos de 3 s; calificación en menos de 5 s |
| **Descripción** | Las páginas deben cargar en menos de 3 segundos y la calificación debe completarse en menos de 5 segundos. |
| **Manejo de situaciones anormales** | Conexión lenta; alta concurrencia. |

| Identificador: RNF-05 | Nombre: Tiempo de generación con IA |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-16 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Pantalla de preguntas generadas |
| **Entrada:** Solicitud de 10 preguntas | **Salida:** Preguntas generadas en menos de 30 s |
| **Descripción** | La generación de 10 preguntas con IA debe tardar menos de 30 segundos. |
| **Manejo de situaciones anormales** | Demora del proveedor de IA. |

| Identificador: RNF-06 | Nombre: Escalabilidad |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-19 a RF-23 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de presentación de la evaluación |
| **Entrada:** 200 intentos simultáneos | **Salida:** Servicio estable |
| **Descripción** | El sistema debe soportar al menos 200 estudiantes presentando una evaluación de forma simultánea. |
| **Manejo de situaciones anormales** | Picos de carga; límites de cuota de Firebase. |

| Identificador: RNF-07 | Nombre: Disponibilidad del sistema |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a toda la plataforma · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Plataforma web |
| **Entrada:** Solicitudes de conexión | **Salida:** Servicio disponible |
| **Descripción** | El sistema debe mantenerse disponible mínimo el 99 % del tiempo, respaldado por la infraestructura de Firebase. |
| **Manejo de situaciones anormales** | Caída del servidor; fallo de red. |

| Identificador: RNF-08 | Nombre: Diseño responsivo |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a toda la plataforma · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Todas las pantallas |
| **Entrada:** Acceso desde computador o celular | **Salida:** Interfaz adaptada al dispositivo |
| **Descripción** | La interfaz debe ser responsiva y usable en computador y celular. |
| **Manejo de situaciones anormales** | Navegador no compatible. |

| Identificador: RNF-09 | Nombre: Accesibilidad |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a toda la plataforma · **¿Crítico?** No |
| **Prioridad de desarrollo:** Baja | **Documentos de visualización asociados:** Todas las pantallas |
| **Entrada:** Uso con teclado o lector de pantalla | **Salida:** Contenido accesible |
| **Descripción** | La plataforma debe cumplir pautas básicas de accesibilidad WCAG 2.1 nivel AA. |
| **Manejo de situaciones anormales** | Imágenes sin texto alternativo; bajo contraste. |

| Identificador: RNF-10 | Nombre: Guardado automático |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-21 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Pantalla de presentación de la evaluación |
| **Entrada:** Respuestas en curso | **Salida:** Respuestas guardadas |
| **Descripción** | Las respuestas deben guardarse automáticamente cada 30 segundos o al cambiar de pregunta. |
| **Manejo de situaciones anormales** | Pérdida de conexión. |

| Identificador: RNF-11 | Nombre: Mantenibilidad |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a toda la plataforma · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Repositorio de código (Git) |
| **Entrada:** Cambios en el código | **Salida:** Código versionado y organizado por módulos |
| **Descripción** | El código debe estar organizado por módulos y bajo control de versiones en Git. |
| **Manejo de situaciones anormales** | Conflictos de versión. |

| Identificador: RNF-12 | Nombre: Protección de datos personales |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-01 y RF-03 · **¿Crítico?** Sí |
| **Prioridad de desarrollo:** Alta | **Documentos de visualización asociados:** Política de tratamiento de datos |
| **Entrada:** Datos personales de los usuarios | **Salida:** Datos tratados con autorización |
| **Descripción** | El tratamiento de datos personales debe cumplir la Ley 1581 de 2012 (Colombia). |
| **Manejo de situaciones anormales** | Solicitud de eliminación de datos; acceso indebido. |

| Identificador: RNF-13 | Nombre: Auditoría |
| --- | --- |
| **Tipo:** No funcional | **Requerimiento que lo utiliza o especializa:** Aplica a RF-05 y RF-06 · **¿Crítico?** No |
| **Prioridad de desarrollo:** Media | **Documentos de visualización asociados:** Registro de auditoría |
| **Entrada:** Acciones sobre evaluaciones | **Salida:** Registro con fecha, hora y usuario |
| **Descripción** | El sistema debe registrar fecha, hora y usuario de la creación, edición y eliminación de evaluaciones. |
| **Manejo de situaciones anormales** | Falla al escribir el registro. |

## 4. Historias de usuario

Formato: Como \[rol\], quiero \[acción\], para \[beneficio\]. Cada historia se relaciona con sus requisitos funcionales.

| Historias de usuario |  |
| --- | --- |
| Número: HU-01 | Usuario: Usuario |
| Nombre historia: Registro e inicio de sesión |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Bajo |
| Puntos estimados: 5 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** usuario, **quiero** registrarme e iniciar sesión con mi correo, **para** acceder a la plataforma. |  |
| Validación: Dado que el usuario tiene una cuenta registrada. Cuando ingresa correo y contraseña válidos. Entonces accede a su panel según su rol. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-02 | Usuario: Usuario |
| Nombre historia: Recuperación de contraseña |  |
| Prioridad en negocio: Media | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** usuario, **quiero** recuperar mi contraseña por correo, **para** volver a entrar si la olvido. |  |
| Validación: Dado que el correo está registrado. Cuando solicita recuperar la contraseña. Entonces recibe un enlace de restablecimiento. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-03 | Usuario: Administrador |
| Nombre historia: Gestión de usuarios y roles |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Medio |
| Puntos estimados: 8 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** administrador, **quiero** crear, editar, desactivar usuarios y asignar su rol, **para** controlar quién usa la plataforma. |  |
| Validación: Dado que el administrador ha iniciado sesión. Cuando cambia el rol o desactiva un usuario. Entonces el cambio se aplica en el siguiente inicio de sesión de ese usuario. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-04 | Usuario: Docente |
| Nombre historia: Creación de evaluaciones |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Medio |
| Puntos estimados: 8 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** crear una evaluación con tiempo límite, fechas, intentos y nota mínima, **para** configurarla según mi curso. |  |
| Validación: Dado que el docente diligencia todos los campos obligatorios. Cuando guarda la evaluación. Entonces queda creada en estado borrador. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-05 | Usuario: Docente |
| Nombre historia: Edición, duplicación y eliminación de evaluaciones |  |
| Prioridad en negocio: Media | Riesgo en desarrollo: Bajo |
| Puntos estimados: 5 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** editar, duplicar y eliminar mis evaluaciones, **para** reutilizarlas y corregirlas. |  |
| Validación: Dado que la evaluación no tiene intentos registrados. Cuando el docente la elimina. Entonces desaparece del listado. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-06 | Usuario: Docente |
| Nombre historia: Publicación de evaluaciones |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** publicar o despublicar una evaluación, **para** decidir cuándo la ven los estudiantes. |  |
| Validación: Dado que la evaluación tiene al menos una pregunta. Cuando el docente la publica. Entonces queda visible para los estudiantes asignados. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-07 | Usuario: Docente |
| Nombre historia: Asignación a grupos |  |
| Prioridad en negocio: Media | Riesgo en desarrollo: Medio |
| Puntos estimados: 5 | Iteración asignada: Iteración 1 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** asignar una evaluación a un grupo, **para** que solo esos estudiantes la presenten. |  |
| Validación: Dado que existe un grupo con estudiantes activos. Cuando el docente le asigna la evaluación. Entonces solo esos estudiantes la ven en su panel. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-08 | Usuario: Docente |
| Nombre historia: Orden aleatorio de preguntas |  |
| Prioridad en negocio: Baja | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 2 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** mostrar preguntas y opciones en orden aleatorio, **para** reducir la copia entre estudiantes. |  |
| Validación: Dado que la opción de aleatorización está activada. Cuando un estudiante inicia un intento. Entonces ve las preguntas y opciones en un orden distinto. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-09 | Usuario: Docente |
| Nombre historia: Creación de preguntas |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Medio |
| Puntos estimados: 8 | Iteración asignada: Iteración 2 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** crear preguntas de selección múltiple, verdadero/falso y respuesta corta con puntaje y retroalimentación, **para** armar mis evaluaciones. |  |
| Validación: Dado que la pregunta tiene enunciado y respuesta correcta. Cuando el docente la guarda. Entonces queda agregada a la evaluación. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-10 | Usuario: Docente |
| Nombre historia: Edición y reordenamiento de preguntas |  |
| Prioridad en negocio: Media | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 2 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** editar, eliminar y reordenar preguntas, **para** ajustar la evaluación. |  |
| Validación: Dado que la evaluación tiene preguntas. Cuando el docente cambia su orden y guarda. Entonces el nuevo orden se conserva. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-11 | Usuario: Docente |
| Nombre historia: Banco de preguntas |  |
| Prioridad en negocio: Baja | Riesgo en desarrollo: Medio |
| Puntos estimados: 5 | Iteración asignada: Iteración 2 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** reutilizar preguntas de un banco, **para** no escribirlas de nuevo. |  |
| Validación: Dado que existen preguntas en el banco. Cuando el docente selecciona algunas. Entonces se agregan a la evaluación sin duplicarse. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-12 | Usuario: Docente |
| Nombre historia: Generación de preguntas con IA |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Alto |
| Puntos estimados: 13 | Iteración asignada: Iteración 2 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** subir un texto o PDF e indicar cantidad, tipo y dificultad, **para** que la IA genere preguntas sobre ese contenido. |  |
| Validación: Dado que el docente sube un archivo PDF o TXT válido. Cuando solicita generar preguntas. Entonces recibe las preguntas propuestas en menos de 30 segundos. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-13 | Usuario: Docente |
| Nombre historia: Revisión de preguntas generadas |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Medio |
| Puntos estimados: 5 | Iteración asignada: Iteración 2 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** revisar, editar, aprobar o descartar las preguntas generadas, **para** asegurar su calidad. |  |
| Validación: Dado que la IA generó preguntas. Cuando el docente aprueba algunas. Entonces solo esas se guardan en la evaluación. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-14 | Usuario: Estudiante |
| Nombre historia: Consulta de evaluaciones disponibles |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** estudiante, **quiero** ver las evaluaciones disponibles para mí, **para** saber qué debo presentar. |  |
| Validación: Dado que el estudiante tiene evaluaciones asignadas y publicadas. Cuando entra a su panel. Entonces ve solo las que están dentro de fechas. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-15 | Usuario: Estudiante |
| Nombre historia: Presentación con temporizador |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Alto |
| Puntos estimados: 8 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** estudiante, **quiero** presentar la evaluación con un temporizador visible, **para** administrar mi tiempo. |  |
| Validación: Dado que el estudiante inició un intento. Cuando el temporizador llega a cero. Entonces el intento se envía automáticamente. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-16 | Usuario: Estudiante |
| Nombre historia: Guardado automático de respuestas |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Medio |
| Puntos estimados: 5 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** estudiante, **quiero** que mis respuestas se guarden solas, **para** no perderlas si se cae la conexión. |  |
| Validación: Dado que el estudiante respondió algunas preguntas. Cuando pierde la conexión y vuelve a entrar. Entonces recupera las respuestas guardadas. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-17 | Usuario: Estudiante |
| Nombre historia: Control de intentos y fechas |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** estudiante, **quiero** que el sistema me indique cuando no tengo intentos o la evaluación está cerrada, **para** entender por qué no puedo entrar. |  |
| Validación: Dado que el estudiante agotó sus intentos. Cuando intenta iniciar uno nuevo. Entonces ve el motivo y no se crea el intento. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-18 | Usuario: Estudiante |
| Nombre historia: Calificación y consulta de nota |  |
| Prioridad en negocio: Alta | Riesgo en desarrollo: Medio |
| Puntos estimados: 8 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** estudiante, **quiero** ver mi nota apenas envíe, y la retroalimentación si el docente la habilita, **para** conocer mi desempeño. |  |
| Validación: Dado que el estudiante envió su intento. Cuando el sistema lo califica. Entonces ve su nota en menos de 5 segundos. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-19 | Usuario: Docente |
| Nombre historia: Resultados y estadísticas |  |
| Prioridad en negocio: Media | Riesgo en desarrollo: Medio |
| Puntos estimados: 8 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** ver resultados por estudiante, promedio, tasa de aprobación y preguntas más falladas, **para** evaluar el aprendizaje del grupo. |  |
| Validación: Dado que la evaluación tiene intentos calificados. Cuando el docente abre el panel de resultados. Entonces ve las estadísticas actualizadas. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-20 | Usuario: Docente |
| Nombre historia: Exportación de resultados |  |
| Prioridad en negocio: Baja | Riesgo en desarrollo: Bajo |
| Puntos estimados: 3 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** exportar los resultados a CSV o Excel, **para** llevarlos a otros sistemas. |  |
| Validación: Dado que existen resultados. Cuando el docente elige exportar. Entonces se descarga un archivo con estudiante, nota, fecha y estado. |  |

| Historias de usuario |  |
| --- | --- |
| Número: HU-21 | Usuario: Docente |
| Nombre historia: Calificación manual de respuestas cortas |  |
| Prioridad en negocio: Media | Riesgo en desarrollo: Medio |
| Puntos estimados: 5 | Iteración asignada: Iteración 3 |
| Programador responsable: Equipo de desarrollo |  |
| Descripción: **Como** docente, **quiero** calificar manualmente las respuestas cortas, **para** corregir lo que la calificación automática no evalúa bien. |  |
| Validación: Dado que hay respuestas cortas pendientes. Cuando el docente asigna un puntaje y guarda. Entonces la nota final se recalcula. |  |

## 5. Casos de uso

| CU-01 | Inicio de sesión y control de acceso |
| --- | --- |
| Historias de Usuario | HU-01 (Registro e inicio de sesión), HU-02 (Recuperación de contraseña) |
| Actores | • Actor principal: Usuario (administrador, docente o estudiante) • Actor secundario: Firebase Authentication |
| Descripción | Permite a los usuarios registrarse, iniciar sesión de forma segura, recuperar su contraseña y acceder según su rol. |
| Precondición | El usuario cuenta con un correo electrónico válido. |
| Flujo Principal | • El usuario ingresa a la pantalla de inicio de sesión. • El usuario digita su correo y contraseña. • El usuario presiona el botón «Ingresar». • El sistema valida las credenciales. • El sistema identifica el rol del usuario. • El sistema muestra el panel correspondiente a su rol. |
| Postcondición | El usuario mantiene una sesión activa con las funcionalidades de su rol. |
| Flujos Alternos | Flujo alterno A: Credenciales incorrectas • El sistema detecta que las credenciales no coinciden. • El sistema muestra un mensaje de acceso no exitoso. • El usuario puede intentar nuevamente. Flujo alterno B: Olvidó la contraseña • El usuario selecciona «¿Olvidaste tu contraseña?». • El sistema envía un enlace de restablecimiento a su correo. |

| CU-02 | Gestionar usuarios y roles |
| --- | --- |
| Historias de Usuario | HU-03 (Gestión de usuarios y roles) |
| Actores | • Actor principal: Administrador • Actor secundario: No aplica |
| Descripción | Permite al administrador crear, editar y desactivar usuarios, y asignarles un rol. |
| Precondición | El administrador ha iniciado sesión (CU-01). |
| Flujo Principal | • El administrador ingresa al panel de usuarios. • El sistema muestra el listado de usuarios. • El administrador selecciona crear o editar un usuario. • El administrador diligencia los datos y el rol. • El sistema valida la información. • El sistema guarda los cambios y muestra un mensaje de confirmación. |
| Postcondición | El usuario queda creado o actualizado con el rol asignado. |
| Flujos Alternos | Flujo alterno A: Correo ya registrado • El sistema informa que el correo existe. • El administrador corrige el dato. Flujo alterno B: Desactivar al único administrador • El sistema bloquea la acción y muestra el motivo. |

| CU-03 | Gestionar evaluaciones |
| --- | --- |
| Historias de Usuario | HU-04 (Creación de evaluaciones), HU-05 (Edición, duplicación y eliminación), HU-08 (Orden aleatorio) |
| Actores | • Actor principal: Docente • Actor secundario: No aplica |
| Descripción | Permite al docente crear, editar, duplicar y eliminar evaluaciones, y configurar su tiempo, fechas, intentos, nota mínima y orden aleatorio. |
| Precondición | El docente ha iniciado sesión (CU-01). |
| Flujo Principal | • El docente ingresa a «Mis evaluaciones». • El docente presiona «Nueva evaluación». • El docente diligencia título, descripción, tiempo límite, fechas, intentos y nota mínima. • El docente activa o no el orden aleatorio. • El sistema valida los datos. • El sistema guarda la evaluación en estado borrador. |
| Postcondición | La evaluación queda creada en borrador, lista para agregarle preguntas. |
| Flujos Alternos | Flujo alterno A: Datos inválidos • El sistema detecta campos vacíos o fecha de cierre anterior a la de apertura. • El sistema resalta los errores. Flujo alterno B: Eliminar con intentos • El sistema impide eliminar una evaluación con intentos registrados. |

| CU-04 | Publicar y asignar evaluación |
| --- | --- |
| Historias de Usuario | HU-06 (Publicación de evaluaciones), HU-07 (Asignación a grupos) |
| Actores | • Actor principal: Docente • Actor secundario: Estudiante (recibe la evaluación) |
| Descripción | Permite al docente asignar una evaluación a un grupo y publicarla para que los estudiantes la presenten. |
| Precondición | La evaluación existe y tiene al menos una pregunta. |
| Flujo Principal | • El docente selecciona una evaluación. • El docente elige el grupo de estudiantes. • El docente presiona «Publicar». • El sistema verifica que tenga preguntas y fechas válidas. • El sistema cambia el estado a publicada. • El sistema la muestra a los estudiantes del grupo. |
| Postcondición | La evaluación es visible para los estudiantes asignados. |
| Flujos Alternos | Flujo alterno A: Evaluación sin preguntas • El sistema no permite publicar y muestra el motivo. Flujo alterno B: Despublicar • El docente presiona «Despublicar». • El sistema oculta la evaluación a los estudiantes. |

| CU-05 | Gestionar preguntas |
| --- | --- |
| Historias de Usuario | HU-09 (Creación de preguntas), HU-10 (Edición y reordenamiento), HU-11 (Banco de preguntas) |
| Actores | • Actor principal: Docente • Actor secundario: No aplica |
| Descripción | Permite al docente crear, editar, eliminar, reordenar y reutilizar preguntas dentro de una evaluación. |
| Precondición | Existe una evaluación creada (CU-03). |
| Flujo Principal | • El docente abre el editor de preguntas de la evaluación. • El docente elige el tipo de pregunta. • El docente escribe enunciado, opciones, respuesta correcta, puntaje y retroalimentación. • El docente presiona «Guardar». • El sistema valida la pregunta. • El sistema la agrega a la evaluación. |
| Postcondición | La pregunta queda guardada en la evaluación y en el banco de preguntas. |
| Flujos Alternos | Flujo alterno A: Sin respuesta correcta • El sistema no guarda y solicita marcar la respuesta correcta. Flujo alterno B: Usar banco de preguntas • El docente busca en el banco. • El docente selecciona preguntas. • El sistema las agrega sin duplicarlas. |

| CU-06 | Generar preguntas con IA |
| --- | --- |
| Historias de Usuario | HU-12 (Generación de preguntas con IA), HU-13 (Revisión de preguntas generadas) |
| Actores | • Actor principal: Docente • Actor secundario: Servicio de IA |
| Descripción | Permite al docente generar preguntas automáticamente a partir de un texto o archivo, revisarlas y guardar las que apruebe. |
| Precondición | Existe una evaluación creada (CU-03) y el servicio de IA está disponible. |
| Flujo Principal | • El docente selecciona «Generar con IA». • El docente pega un texto o sube un archivo PDF o TXT. • El docente indica cantidad, tipo y dificultad. • El sistema envía el contenido al servicio de IA. • El servicio de IA devuelve las preguntas en formato estructurado. • El sistema muestra las preguntas propuestas. • El docente edita, aprueba o descarta cada una. • El sistema guarda las preguntas aprobadas. |
| Postcondición | Las preguntas aprobadas quedan agregadas a la evaluación. |
| Flujos Alternos | Flujo alterno A: Archivo no válido • El sistema rechaza el formato o tamaño y muestra el motivo. Flujo alterno B: Falla del servicio de IA • El sistema informa que no fue posible generar las preguntas. • El docente puede reintentar o crear preguntas manualmente (CU-05). |

| CU-07 | Presentar evaluación |
| --- | --- |
| Historias de Usuario | HU-14 (Consulta de evaluaciones disponibles), HU-15 (Presentación con temporizador), HU-16 (Guardado automático), HU-17 (Control de intentos y fechas) |
| Actores | • Actor principal: Estudiante • Actor secundario: No aplica |
| Descripción | Permite al estudiante consultar sus evaluaciones disponibles y presentarlas con temporizador y guardado automático. |
| Precondición | El estudiante ha iniciado sesión y tiene una evaluación publicada y asignada (CU-04). |
| Flujo Principal | • El estudiante ingresa a su panel. • El sistema muestra las evaluaciones disponibles. • El estudiante selecciona una y presiona «Iniciar». • El sistema verifica intentos y fechas. • El sistema registra el inicio del intento y activa el temporizador. • El estudiante responde las preguntas. • El sistema guarda las respuestas automáticamente. • El estudiante presiona «Enviar». • El sistema registra el envío y ejecuta la calificación (CU-08). |
| Postcondición | El intento queda registrado y enviado para calificar. |
| Flujos Alternos | Flujo alterno A: Intentos agotados o evaluación cerrada • El sistema muestra el motivo y no crea el intento. Flujo alterno B: Tiempo agotado • El sistema envía el intento automáticamente. Flujo alterno C: Pérdida de conexión • El estudiante vuelve a entrar. • El sistema recupera las respuestas guardadas y el tiempo restante. |

| CU-08 | Calificar automáticamente |
| --- | --- |
| Historias de Usuario | HU-18 (Calificación y consulta de nota) |
| Actores | • Actor principal: Sistema (Cloud Functions) • Actor secundario: Estudiante |
| Descripción | Califica el intento comparando las respuestas con las correctas y muestra la nota al estudiante. |
| Precondición | El estudiante envió su intento (CU-07). |
| Flujo Principal | • El sistema recibe el intento enviado. • El sistema compara cada respuesta con la respuesta correcta. • El sistema suma los puntajes obtenidos. • El sistema calcula la nota y si aprobó. • El sistema guarda el resultado. • El sistema muestra la nota al estudiante y la retroalimentación si está habilitada. |
| Postcondición | El resultado queda registrado y visible para el estudiante y el docente. |
| Flujos Alternos | Flujo alterno A: Respuestas cortas • El sistema marca esas preguntas como pendientes. • La nota final se completa con la calificación manual (CU-10). Flujo alterno B: Error en la calificación • El sistema registra el error y reintenta el cálculo. |

| CU-09 | Consultar resultados y estadísticas |
| --- | --- |
| Historias de Usuario | HU-19 (Resultados y estadísticas), HU-20 (Exportación de resultados) |
| Actores | • Actor principal: Docente • Actor secundario: No aplica |
| Descripción | Permite al docente ver los resultados por estudiante, las estadísticas del grupo y exportarlos. |
| Precondición | La evaluación tiene al menos un intento calificado. |
| Flujo Principal | • El docente selecciona una evaluación. • El docente ingresa a «Resultados». • El sistema muestra la nota de cada estudiante. • El sistema muestra promedio, tasa de aprobación y preguntas más falladas. • El docente presiona «Exportar» y elige CSV o Excel. • El sistema genera y descarga el archivo. |
| Postcondición | El docente conoce el desempeño del grupo y tiene el archivo exportado. |
| Flujos Alternos | Flujo alterno A: Sin intentos • El sistema informa que aún no hay resultados. Flujo alterno B: Error al exportar • El sistema muestra un mensaje y permite reintentar. |

| CU-10 | Calificar manualmente respuestas cortas |
| --- | --- |
| Historias de Usuario | HU-21 (Calificación manual de respuestas cortas) |
| Actores | • Actor principal: Docente • Actor secundario: Estudiante (recibe la nota actualizada) |
| Descripción | Permite al docente revisar las respuestas cortas y asignarles un puntaje. |
| Precondición | Existen respuestas cortas pendientes de revisión (CU-08). |
| Flujo Principal | • El docente ingresa a «Pendientes de revisión». • El sistema muestra las respuestas cortas de los estudiantes. • El docente asigna un puntaje a cada una. • El docente presiona «Guardar». • El sistema recalcula la nota final. • El sistema actualiza el resultado del estudiante. |
| Postcondición | La nota final del estudiante queda completa y actualizada. |
| Flujos Alternos | Flujo alterno A: Puntaje inválido • El sistema detecta un puntaje mayor al máximo de la pregunta. • El sistema solicita corregirlo. |
