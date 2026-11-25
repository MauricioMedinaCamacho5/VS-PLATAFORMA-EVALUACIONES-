<?php
declare(strict_types=1);

// Crear (sin id) o editar (?id=) una evaluación del docente que ha iniciado sesión.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'evaluaciones.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET' && $metodo !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido.');
}

$idRecibido = $metodo === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$evaluacionId = null;
if ($idRecibido !== '') {
    $evaluacionId = leerId($idRecibido);
    if ($evaluacionId === null) {
        redirigirConMensaje('evaluaciones.php', 'La evaluación solicitada no es válida.', 'danger');
    }
}

$errores = [];
try {
    $clasesPropias = clasesDelDocente($db, $docenteId);
    $existente = $evaluacionId === null ? null : buscarEvaluacionPropia($db, $evaluacionId, $docenteId);
    if ($evaluacionId !== null && $existente === null) {
        redirigirConMensaje('evaluaciones.php', 'La evaluación no existe o no te pertenece.', 'danger');
    }
    $valores = valoresFormulario($existente);
    if ($evaluacionId !== null) {
        $valores['clases'] = idsClasesAsignadas($db, $evaluacionId);
    }

    if ($metodo === 'POST') {
        verificarCsrfFormulario();
        [$valores, $datos, $errores] = validarEvaluacion($_POST);
        [$claseIds, $errorClases] = leerClasesSeleccionadas($_POST['clases'] ?? null, $clasesPropias);
        $valores['clases'] = $claseIds;
        if ($errorClases !== null) {
            $errores['clases'] = $errorClases;
        }

        if (!$errores) {
            $db->beginTransaction();
            $publicada = isset($_POST['publicada']) && (int)$_POST['publicada'] === 1 ? 1 : 0;
            if ($evaluacionId === null) {
                $db->prepare(
                    'INSERT INTO evaluaciones (docente_id, titulo, descripcion, tiempo_minutos, apertura, cierre,
                        intentos_permitidos, nota_minima, orden_aleatorio, mostrar_retroalimentacion, publicada)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $docenteId, $datos['titulo'], $datos['descripcion'], $datos['tiempo_minutos'],
                    $datos['apertura'], $datos['cierre'], $datos['intentos_permitidos'], $datos['nota_minima'],
                    $datos['orden_aleatorio'], $datos['mostrar_retroalimentacion'], $publicada,
                ]);
                $nuevoId = (int)$db->lastInsertId();
                guardarClasesEvaluacion($db, $nuevoId, $claseIds);
                registrarAuditoria($db, $docenteId, $nuevoId, 'evaluacion_crear', [
                    'titulo' => $datos['titulo'], 'clases' => $claseIds, 'publicada' => $publicada,
                ]);
                $db->commit();
                $mensaje = $publicada === 1
                    ? 'Evaluación creada y publicada con éxito.'
                    : 'Evaluación creada como borrador.';
                redirigirConMensaje('evaluaciones.php', $mensaje);
            }

            $anterior = buscarEvaluacionPropia($db, $evaluacionId, $docenteId, true);
            if ($anterior === null) {
                $db->rollBack();
                redirigirConMensaje('evaluaciones.php', 'La evaluación no existe o no te pertenece.', 'danger');
            }
            $cambios = camposModificados($anterior, $datos);
            if ((int)$anterior['publicada'] !== $publicada) {
                $cambios['publicada'] = ['antes' => (int)$anterior['publicada'], 'despues' => $publicada];
            }
            $clasesAntes = idsClasesAsignadas($db, $evaluacionId);
            if ($clasesAntes !== $claseIds) {
                $cambios['clases'] = ['antes' => $clasesAntes, 'despues' => $claseIds];
            }
            if (!$cambios) {
                $db->rollBack();
                redirigirConMensaje('evaluaciones.php', 'No había cambios que guardar.', 'info');
            }
            $db->prepare(
                'UPDATE evaluaciones SET titulo = ?, descripcion = ?, tiempo_minutos = ?, apertura = ?, cierre = ?,
                    intentos_permitidos = ?, nota_minima = ?, orden_aleatorio = ?, mostrar_retroalimentacion = ?, publicada = ?
                 WHERE id = ? AND docente_id = ?'
            )->execute([
                $datos['titulo'], $datos['descripcion'], $datos['tiempo_minutos'], $datos['apertura'],
                $datos['cierre'], $datos['intentos_permitidos'], $datos['nota_minima'],
                $datos['orden_aleatorio'], $datos['mostrar_retroalimentacion'], $publicada,
                $evaluacionId, $docenteId,
            ]);
            guardarClasesEvaluacion($db, $evaluacionId, $claseIds);
            registrarAuditoria($db, $docenteId, $evaluacionId, 'evaluacion_editar', ['cambios' => $cambios]);
            $db->commit();
            redirigirConMensaje('evaluaciones.php', 'Evaluación actualizada.');
        }
        http_response_code(422);
    }
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en el formulario de evaluaciones: ' . $exception);
    http_response_code(500);
    exit('No se pudo guardar la evaluación. Inténtalo más tarde.');
}

$editando = $evaluacionId !== null;
$publicada = $editando ? ((int)$existente['publicada'] === 1) : true;
$titulo = $editando ? 'Editar evaluación' : 'Nueva evaluación';
$seccion = 'evaluaciones';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'evaluacion_form.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
