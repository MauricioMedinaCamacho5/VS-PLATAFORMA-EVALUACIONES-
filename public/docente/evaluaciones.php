<?php
declare(strict_types=1);

// "Mis evaluaciones": listado y acciones (duplicar, eliminar, publicar, despublicar).

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'evaluaciones.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrfFormulario();
    $accion = $_POST['accion'] ?? '';
    $id = leerId($_POST['id'] ?? null);
    if (!in_array($accion, ['duplicar', 'eliminar', 'publicar', 'despublicar'], true) || $id === null) {
        redirigirConMensaje('evaluaciones.php', 'Acción no válida.', 'danger');
    }

    try {
        $db->beginTransaction();
        $evaluacion = buscarEvaluacionPropia($db, $id, $docenteId, true);
        if ($evaluacion === null) {
            $db->rollBack();
            redirigirConMensaje('evaluaciones.php', 'La evaluación no existe o no te pertenece.', 'danger');
        }

        if ($accion === 'publicar' || $accion === 'despublicar') {
            $publicar = $accion === 'publicar';
            if ((int)$evaluacion['publicada'] === ($publicar ? 1 : 0)) {
                $db->rollBack();
                redirigirConMensaje('evaluaciones.php', $publicar ? 'La evaluación ya estaba publicada.' : 'La evaluación ya era un borrador.', 'info');
            }
            if ($publicar) {
                $cerrada = $db->prepare('SELECT cierre <= NOW() FROM evaluaciones WHERE id = ?');
                $cerrada->execute([$id]);
                if ((int)$cerrada->fetchColumn() === 1) {
                    $db->rollBack();
                    redirigirConMensaje('evaluaciones.php', 'No se puede publicar: la fecha de cierre ya pasó. Edítala primero.', 'warning');
                }
            }
            $db->prepare('UPDATE evaluaciones SET publicada = ? WHERE id = ? AND docente_id = ?')
                ->execute([$publicar ? 1 : 0, $id, $docenteId]);
            registrarAuditoria($db, $docenteId, $id, $publicar ? 'evaluacion_publicar' : 'evaluacion_despublicar',
                ['titulo' => $evaluacion['titulo']]);
            $db->commit();
            if ($publicar) {
                $avisos = [];
                if (!idsClasesAsignadas($db, $id)) {
                    $avisos[] = 'no está asignada a ninguna clase (ningún estudiante la verá; edítala y marca una clase)';
                }
                $preguntas = $db->prepare('SELECT COUNT(*) FROM preguntas WHERE evaluacion_id = ?');
                $preguntas->execute([$id]);
                if ((int)$preguntas->fetchColumn() === 0) {
                    $avisos[] = 'no tiene preguntas (los estudiantes no podrán presentarla; pulsa «Preguntas»)';
                }
                if ($avisos) {
                    redirigirConMensaje('evaluaciones.php', 'Evaluación publicada, pero ' . implode(' y ', $avisos) . '.', 'warning');
                }
            }
            redirigirConMensaje('evaluaciones.php', $publicar ? 'Evaluación publicada.' : 'Evaluación devuelta a borrador.');
        }

        if ($accion === 'duplicar') {
            $tituloCopia = mb_substr('Copia de ' . $evaluacion['titulo'], 0, 200, 'UTF-8');
            $db->prepare(
                'INSERT INTO evaluaciones (docente_id, titulo, descripcion, tiempo_minutos, apertura, cierre,
                    intentos_permitidos, nota_minima, orden_aleatorio, mostrar_retroalimentacion, publicada)
                 SELECT docente_id, ?, descripcion, tiempo_minutos, apertura, cierre,
                    intentos_permitidos, nota_minima, orden_aleatorio, mostrar_retroalimentacion, 0
                 FROM evaluaciones WHERE id = ? AND docente_id = ?'
            )->execute([$tituloCopia, $id, $docenteId]);
            $copiaId = (int)$db->lastInsertId();
            guardarClasesEvaluacion($db, $copiaId, idsClasesAsignadas($db, $id));

            // Copia también las preguntas y sus opciones (si las hay).
            $preguntas = $db->prepare('SELECT id FROM preguntas WHERE evaluacion_id = ? ORDER BY posicion, id');
            $preguntas->execute([$id]);
            $copiarPregunta = $db->prepare(
                'INSERT INTO preguntas (evaluacion_id, tipo, enunciado, respuesta_corta, puntaje, retroalimentacion, posicion)
                 SELECT ?, tipo, enunciado, respuesta_corta, puntaje, retroalimentacion, posicion FROM preguntas WHERE id = ?'
            );
            $copiarOpciones = $db->prepare(
                'INSERT INTO opciones (pregunta_id, texto, correcta, posicion)
                 SELECT ?, texto, correcta, posicion FROM opciones WHERE pregunta_id = ?'
            );
            $totalPreguntas = 0;
            foreach ($preguntas->fetchAll(PDO::FETCH_COLUMN) as $preguntaId) {
                $copiarPregunta->execute([$copiaId, $preguntaId]);
                $copiarOpciones->execute([(int)$db->lastInsertId(), $preguntaId]);
                $totalPreguntas++;
            }
            registrarAuditoria($db, $docenteId, $copiaId, 'evaluacion_crear', [
                'titulo' => $tituloCopia, 'duplicada_de' => $id, 'preguntas_copiadas' => $totalPreguntas,
            ]);
            $db->commit();
            redirigirConMensaje('evaluaciones.php', 'Evaluación duplicada como borrador.');
        }

        // Eliminar: no se borran evaluaciones con intentos de estudiantes, para no perder sus resultados.
        $intentos = $db->prepare('SELECT COUNT(*) FROM intentos WHERE evaluacion_id = ?');
        $intentos->execute([$id]);
        if ((int)$intentos->fetchColumn() > 0) {
            $db->rollBack();
            redirigirConMensaje('evaluaciones.php',
                'No se puede eliminar: hay estudiantes que ya la presentaron. Puedes despublicarla.', 'warning');
        }
        // La auditoría se registra antes del borrado; al borrar, su evaluacion_id queda en NULL
        // (ON DELETE SET NULL), por eso el id y el título se guardan también en el detalle.
        registrarAuditoria($db, $docenteId, $id, 'evaluacion_eliminar', ['titulo' => $evaluacion['titulo']]);
        $db->prepare('DELETE FROM evaluaciones WHERE id = ? AND docente_id = ?')->execute([$id, $docenteId]);
        $db->commit();
        redirigirConMensaje('evaluaciones.php', 'Evaluación eliminada.');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Error en acción de evaluaciones: ' . $exception);
        http_response_code(500);
        exit('No se pudo completar la acción. Inténtalo más tarde.');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido.');
}

try {
    // Paso de confirmación antes de eliminar (?eliminar=ID).
    $porEliminar = null;
    if (isset($_GET['eliminar'])) {
        $idEliminar = leerId($_GET['eliminar']);
        $porEliminar = $idEliminar === null ? null : buscarEvaluacionPropia($db, $idEliminar, $docenteId);
        if ($porEliminar === null) {
            redirigirConMensaje('evaluaciones.php', 'La evaluación no existe o no te pertenece.', 'danger');
        }
    }

    $consulta = $db->prepare(
        'SELECT e.id, e.titulo, e.tiempo_minutos, e.apertura, e.cierre, e.intentos_permitidos, e.publicada,
                (SELECT COUNT(*) FROM evaluacion_clases ec WHERE ec.evaluacion_id = e.id) AS total_clases,
                (SELECT COUNT(*) FROM preguntas p WHERE p.evaluacion_id = e.id) AS total_preguntas
         FROM evaluaciones e WHERE e.docente_id = ? ORDER BY e.creado_en DESC, e.id DESC'
    );
    $consulta->execute([$docenteId]);
    $evaluaciones = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al listar evaluaciones: ' . $exception);
    http_response_code(500);
    exit('No se pudieron consultar las evaluaciones. Inténtalo más tarde.');
}

$mensaje = tomarMensaje();
$titulo = 'Mis evaluaciones';
$seccion = 'evaluaciones';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'evaluaciones_lista.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
