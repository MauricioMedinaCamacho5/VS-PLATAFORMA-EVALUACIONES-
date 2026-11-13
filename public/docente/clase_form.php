<?php
declare(strict_types=1);

// Crear (sin id) o editar (?id=) una clase del docente.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clases.php';
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
$claseId = null;
if ($idRecibido !== '') {
    $claseId = leerId($idRecibido);
    if ($claseId === null) {
        redirigirConMensaje('clases.php', 'La clase solicitada no es válida.', 'danger');
    }
}

$errores = [];
try {
    $existente = $claseId === null ? null : buscarClasePropia($db, $claseId, $docenteId);
    if ($claseId !== null && $existente === null) {
        redirigirConMensaje('clases.php', 'La clase no existe o no te pertenece.', 'danger');
    }
    $valores = [
        'nombre' => $existente['nombre'] ?? '',
        'institucion' => $existente['institucion'] ?? '',
        'descripcion' => $existente['descripcion'] ?? '',
        'color' => $existente['color'] ?? array_key_first(COLORES_CLASE),
    ];

    if ($metodo === 'POST') {
        verificarCsrfFormulario();
        $texto = static fn(string $campo): string => is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
        $valores = [
            'nombre' => $texto('nombre'),
            'institucion' => $texto('institucion'),
            'descripcion' => $texto('descripcion'),
            'color' => strtoupper($texto('color')),
        ];
        foreach (['nombre', 'institucion', 'descripcion'] as $campo) {
            if (!mb_check_encoding($valores[$campo], 'UTF-8')) {
                $valores[$campo] = mb_scrub($valores[$campo], 'UTF-8');
                $errores[$campo] = 'El texto contiene caracteres no válidos.';
            }
        }
        if ($valores['nombre'] === '') {
            $errores['nombre'] = 'Escribe el nombre de la clase.';
        } elseif (!isset($errores['nombre']) && mb_strlen($valores['nombre'], 'UTF-8') > 150) {
            $errores['nombre'] = 'El nombre admite como máximo 150 caracteres.';
        }
        if (!isset($errores['institucion']) && mb_strlen($valores['institucion'], 'UTF-8') > 150) {
            $errores['institucion'] = 'La institución admite como máximo 150 caracteres.';
        }
        if (!isset($errores['descripcion']) && mb_strlen($valores['descripcion'], 'UTF-8') > 2000) {
            $errores['descripcion'] = 'La descripción admite como máximo 2000 caracteres.';
        }
        if (!isset(COLORES_CLASE[$valores['color']])) {
            $errores['color'] = 'Elige uno de los colores disponibles.';
            $valores['color'] = array_key_first(COLORES_CLASE);
        }

        if (!$errores) {
            $db->beginTransaction();
            $institucionId = idInstitucion($db, $valores['institucion']);
            if ($claseId === null) {
                $db->prepare(
                    'INSERT INTO clases (docente_id, institucion_id, nombre, descripcion, color) VALUES (?, ?, ?, ?, ?)'
                )->execute([$docenteId, $institucionId, $valores['nombre'], $valores['descripcion'], $valores['color']]);
                $nuevaId = (int)$db->lastInsertId();
                $codigo = asignarCodigoNuevo($db, $nuevaId);
                registrarAuditoria($db, $docenteId, null, 'clase_crear', ['clase_id' => $nuevaId, 'nombre' => $valores['nombre'], 'codigo' => $codigo]);
                $db->commit();
                redirigirConMensaje('clase.php?id=' . $nuevaId, 'Clase creada. Comparte el código ' . $codigo . ' con tus estudiantes.');
            }
            $db->prepare(
                'UPDATE clases SET institucion_id = ?, nombre = ?, descripcion = ?, color = ? WHERE id = ? AND docente_id = ?'
            )->execute([$institucionId, $valores['nombre'], $valores['descripcion'], $valores['color'], $claseId, $docenteId]);
            registrarAuditoria($db, $docenteId, null, 'clase_editar', ['clase_id' => $claseId, 'nombre' => $valores['nombre']]);
            $db->commit();
            redirigirConMensaje('clase.php?id=' . $claseId, 'Clase actualizada.');
        }
        http_response_code(422);
    }
    $instituciones = nombresInstituciones($db);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en el formulario de clases: ' . $exception);
    http_response_code(500);
    exit('No se pudo guardar la clase. Inténtalo más tarde.');
}

$editando = $claseId !== null;
$titulo = $editando ? 'Editar clase' : 'Nueva clase';
$seccion = 'clases';
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'clase_form.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
