<?php
declare(strict_types=1);

// "Generar con IA": formulario con tres fuentes (pegar texto, subir archivo, desde un tema).
// POST accion=generar crea un borrador y abre la revisión; accion=resumen escribe un texto sobre el tema.

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ia.php';
$usuario = requerirRol('docente');
$docenteId = (int)$usuario['id'];
$db = db();

$errorConfiguracion = iaErrorConfiguracion();
$errores = [];
$avisoResumen = false;
$valores = [
    'evaluacion' => (string)($_GET['evaluacion'] ?? ''), 'fuente' => 'texto', 'texto' => '',
    'tema' => '', 'nivel' => '', 'subtemas' => '', 'desde_tema' => '0',
    'cantidad' => '5', 'tipo' => 'seleccion_multiple', 'dificultad' => 'media', 'puntaje' => '1', 'instrucciones' => '',
];

try {
    $consulta = $db->prepare(
        'SELECT e.id, e.titulo, (SELECT COUNT(*) FROM intentos i WHERE i.evaluacion_id = e.id) AS intentos
         FROM evaluaciones e WHERE e.docente_id = ? ORDER BY e.creado_en DESC, e.id DESC'
    );
    $consulta->execute([$docenteId]);
    $evaluaciones = $consulta->fetchAll();
} catch (Throwable $exception) {
    error_log('Error al cargar el formulario de IA: ' . $exception);
    http_response_code(500);
    exit('No se pudo cargar el formulario. Inténtalo más tarde.');
}
$evaluacionesEditables = [];
foreach ($evaluaciones as $evaluacion) {
    if ((int)$evaluacion['intentos'] === 0) {
        $evaluacionesEditables[(int)$evaluacion['id']] = $evaluacion['titulo'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si el archivo supera post_max_size, PHP descarta todo el POST (también el token CSRF).
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        redirigirConMensaje('ia.php', 'El archivo es demasiado grande. El máximo es 5 MB.', 'danger');
    }
    verificarCsrfFormulario();
    $texto = static fn(string $campo, int $maximo): string => is_string($_POST[$campo] ?? null)
        ? mb_substr(trim(mb_scrub($_POST[$campo], 'UTF-8')), 0, $maximo, 'UTF-8') : '';
    $valores = [
        'evaluacion' => $texto('evaluacion', 20), 'fuente' => $texto('fuente', 10), 'texto' => $texto('texto', 200000),
        'tema' => $texto('tema', 200), 'nivel' => $texto('nivel', 100), 'subtemas' => $texto('subtemas', 500),
        'desde_tema' => ($_POST['desde_tema'] ?? '') === '1' ? '1' : '0',
        'cantidad' => $texto('cantidad', 5), 'tipo' => $texto('tipo', 30), 'dificultad' => $texto('dificultad', 10),
        'puntaje' => $texto('puntaje', 10), 'instrucciones' => $texto('instrucciones', 500),
    ];
    $accion = $_POST['accion'] ?? '';

    if ($errorConfiguracion !== null) {
        $errores['general'] = $errorConfiguracion;
    } elseif ($accion === 'resumen') {
        if ($valores['tema'] === '') {
            $errores['tema'] = 'Escribe el tema para generar el resumen.';
        } else {
            [$resumen, $error] = generarResumenTema($valores['tema'], $valores['nivel'], $valores['subtemas']);
            if ($resumen === null) {
                $errores['general'] = $error;
                $valores['fuente'] = 'tema';
            } else {
                // El resumen pasa a la pestaña "Pegar texto" para que el docente lo revise y edite.
                $valores['texto'] = $resumen;
                $valores['fuente'] = 'texto';
                $valores['desde_tema'] = '1';
                $avisoResumen = true;
            }
        }
    } elseif ($accion === 'generar') {
        $evaluacionId = leerId($valores['evaluacion']);
        if ($evaluacionId === null || !isset($evaluacionesEditables[$evaluacionId])) {
            $errores['evaluacion'] = 'Elige una de tus evaluaciones (que todavía no tenga intentos).';
        }
        $cantidad = filter_var($valores['cantidad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => IA_MAX_PREGUNTAS]]);
        if ($cantidad === false) {
            $errores['cantidad'] = 'La cantidad debe estar entre 1 y ' . IA_MAX_PREGUNTAS . '.';
        }
        if (!isset(IA_TIPOS[$valores['tipo']])) {
            $errores['tipo'] = 'Elige un tipo de pregunta.';
        }
        if (!isset(IA_DIFICULTADES[$valores['dificultad']])) {
            $errores['dificultad'] = 'Elige una dificultad.';
        }
        $puntaje = filter_var(str_replace(',', '.', $valores['puntaje']), FILTER_VALIDATE_FLOAT);
        if ($puntaje === false || $puntaje <= 0 || $puntaje > 1000) {
            $errores['puntaje'] = 'El puntaje debe ser mayor que 0 (máximo 1000).';
        }

        $fuente = null;
        $recortado = false;
        $nombreArchivo = '';
        if ($valores['fuente'] === 'tema') {
            if (mb_strlen($valores['tema'], 'UTF-8') < 3) {
                $errores['tema'] = 'Escribe el tema sobre el que quieres las preguntas.';
            } else {
                $fuente = ['tipo' => 'tema', 'tema' => $valores['tema'], 'nivel' => $valores['nivel'], 'subtemas' => $valores['subtemas']];
            }
        } elseif ($valores['fuente'] === 'archivo') {
            [$extraido, $error] = extraerTextoArchivo($_FILES['archivo'] ?? null);
            $nombreArchivo = is_array($_FILES['archivo'] ?? null) ? mb_substr(basename((string)$_FILES['archivo']['name']), 0, 120) : '';
            if ($extraido === null) {
                $errores['archivo'] = $error;
            } elseif (mb_strlen($extraido, 'UTF-8') < 100) {
                $errores['archivo'] = 'El archivo tiene muy poco texto para crear preguntas (mínimo 100 caracteres).';
            } else {
                [$base, $recortado] = recortarTextoBase($extraido);
                $fuente = ['tipo' => 'texto', 'texto' => $base];
            }
        } else {
            $valores['fuente'] = 'texto';
            $limpio = limpiarTextoBase($valores['texto']);
            if ($limpio === '') {
                $errores['texto'] = 'Pega el texto del que saldrán las preguntas.';
            } elseif (mb_strlen($limpio, 'UTF-8') < 100) {
                $errores['texto'] = 'El texto es muy corto para crear preguntas (mínimo 100 caracteres).';
            } else {
                [$base, $recortado] = recortarTextoBase($limpio);
                $fuente = ['tipo' => 'texto', 'texto' => $base];
            }
        }

        if (!$errores && $fuente !== null) {
            $parametros = [
                'cantidad' => $cantidad, 'tipo' => $valores['tipo'], 'dificultad' => $valores['dificultad'],
                'puntaje' => round($puntaje, 2), 'instrucciones' => $valores['instrucciones'],
            ];
            $resultado = generarPreguntasIa($parametros, $fuente);
            if ($resultado['error'] !== null) {
                $errores['general'] = $resultado['error'];
            } else {
                $sinTextoBase = $fuente['tipo'] === 'tema' || $valores['desde_tema'] === '1';
                $avisos = [];
                if ($recortado) {
                    $avisos[] = 'El texto era muy largo: se usaron solo los primeros ' . number_format(IA_MAX_CARACTERES, 0, ',', '.') . ' caracteres.';
                }
                if ($resultado['descartadas'] > 0) {
                    $avisos[] = 'Se descartaron ' . $resultado['descartadas'] . ' pregunta(s) mal formadas que devolvió la IA.';
                }
                if (count($resultado['preguntas']) < $cantidad) {
                    $avisos[] = 'Pediste ' . $cantidad . ' y quedaron ' . count($resultado['preguntas']) . ' válidas. Puedes usar «Generar más».';
                }
                $borradorId = crearBorradorIa([
                    'evaluacion_id' => $evaluacionId, 'fuente' => $fuente, 'parametros' => $parametros,
                    'origen' => $fuente['tipo'] === 'tema' ? 'tema' : ($valores['fuente'] === 'archivo' ? 'archivo' : 'texto'),
                    'sin_texto_base' => $sinTextoBase, 'avisos' => $avisos, 'nuevas' => $resultado['preguntas'],
                    'generadas' => count($resultado['preguntas']),
                ]);
                registrarAuditoria($db, $docenteId, $evaluacionId, 'ia_generar', [
                    'borrador' => $borradorId, 'origen' => $_SESSION['ia_borradores'][$borradorId]['origen'],
                    'archivo' => $nombreArchivo !== '' ? $nombreArchivo : null, 'modelo' => GEMINI_MODELO,
                    'pedidas' => $cantidad, 'generadas' => count($resultado['preguntas']),
                    'descartadas' => $resultado['descartadas'], 'texto_recortado' => $recortado,
                ]);
                header('Location: ia_revision.php?borrador=' . $borradorId, true, 303);
                exit;
            }
        }
        http_response_code(422);
    } else {
        $errores['general'] = 'Acción no válida.';
    }
}

$mensaje = tomarMensaje();
$titulo = 'Generar preguntas con IA';
$subtitulo = 'La IA propone las preguntas; tú las revisas y apruebas antes de guardarlas.';
$seccion = 'ia';
$scripts = ['../assets/js/ia.js'];
$vista = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'vistas'
    . DIRECTORY_SEPARATOR . 'docente_ia.php';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
