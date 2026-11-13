<?php
declare(strict_types=1);

// Funciones compartidas del módulo de evaluaciones del docente.

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

const NOTA_MAXIMA = 100;          // Escala de calificación; ajústala si usas otra (por ejemplo 5).
const TIEMPO_MAXIMO_MINUTOS = 1440;
const INTENTOS_MAXIMOS = 100;

function verificarCsrfFormulario(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('La sesión del formulario ha vencido. Recarga la página e inténtalo de nuevo.');
    }
}

function redirigirConMensaje(string $destino, string $mensaje, string $tipo = 'success'): never
{
    $_SESSION['mensaje_evaluaciones'] = ['texto' => $mensaje, 'tipo' => $tipo];
    header('Location: ' . $destino, true, 303);
    exit;
}

function tomarMensaje(): ?array
{
    $mensaje = $_SESSION['mensaje_evaluaciones'] ?? null;
    unset($_SESSION['mensaje_evaluaciones']);
    return is_array($mensaje) ? $mensaje : null;
}

function leerId(mixed $valor): ?int
{
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function registrarAuditoria(PDO $db, int $usuarioId, ?int $evaluacionId, string $accion, array $detalle = []): void
{
    // Si la evaluación se elimina, la columna evaluacion_id pasa a NULL (ON DELETE SET NULL);
    // copiar el id en el detalle conserva la trazabilidad de todo su historial.
    if ($evaluacionId !== null) {
        $detalle = ['evaluacion_id' => $evaluacionId] + $detalle;
    }
    $db->prepare('INSERT INTO auditoria (usuario_id, evaluacion_id, accion, detalle) VALUES (?, ?, ?, ?)')
        ->execute([
            $usuarioId,
            $evaluacionId,
            $accion,
            $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        ]);
}

/** Devuelve la evaluación solo si pertenece al docente indicado. */
function buscarEvaluacionPropia(PDO $db, int $id, int $docenteId, bool $bloquear = false): ?array
{
    $sql = 'SELECT * FROM evaluaciones WHERE id = ? AND docente_id = ?' . ($bloquear ? ' FOR UPDATE' : '');
    $consulta = $db->prepare($sql);
    $consulta->execute([$id, $docenteId]);
    $fila = $consulta->fetch();
    return $fila === false ? null : $fila;
}

function leerFechaFormulario(string $valor): ?DateTimeImmutable
{
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $valor);
    return $fecha !== false && $fecha->format('Y-m-d\TH:i') === $valor ? $fecha : null;
}

/** Convierte una fila de la base de datos en los valores que muestra el formulario. */
function valoresFormulario(?array $fila): array
{
    if ($fila === null) {
        return [
            'titulo' => '', 'descripcion' => '', 'tiempo_minutos' => '60',
            'apertura' => '', 'cierre' => '', 'intentos_permitidos' => '1',
            'nota_minima' => '60', 'orden_aleatorio' => false, 'mostrar_retroalimentacion' => false,
            'clases' => [],
        ];
    }
    return [
        'titulo' => $fila['titulo'],
        'descripcion' => $fila['descripcion'],
        'tiempo_minutos' => (string)$fila['tiempo_minutos'],
        'apertura' => date('Y-m-d\TH:i', strtotime((string)$fila['apertura'])),
        'cierre' => date('Y-m-d\TH:i', strtotime((string)$fila['cierre'])),
        'intentos_permitidos' => (string)$fila['intentos_permitidos'],
        'nota_minima' => rtrim(rtrim(number_format((float)$fila['nota_minima'], 2, '.', ''), '0'), '.'),
        'orden_aleatorio' => (int)$fila['orden_aleatorio'] === 1,
        'mostrar_retroalimentacion' => (int)$fila['mostrar_retroalimentacion'] === 1,
        'clases' => [], // Se completa con idsClasesAsignadas().
    ];
}

/**
 * Valida lo enviado por el formulario.
 * Devuelve [valores para volver a mostrar, datos listos para la BD, lista de errores].
 */
function validarEvaluacion(array $entrada): array
{
    $texto = static fn(string $campo): string => is_string($entrada[$campo] ?? null) ? trim($entrada[$campo]) : '';
    $valores = [
        'titulo' => $texto('titulo'),
        'descripcion' => $texto('descripcion'),
        'tiempo_minutos' => $texto('tiempo_minutos'),
        'apertura' => $texto('apertura'),
        'cierre' => $texto('cierre'),
        'intentos_permitidos' => $texto('intentos_permitidos'),
        'nota_minima' => $texto('nota_minima'),
        'orden_aleatorio' => ($entrada['orden_aleatorio'] ?? '') === '1',
        'mostrar_retroalimentacion' => ($entrada['mostrar_retroalimentacion'] ?? '') === '1',
        'clases' => [], // Las clases se validan aparte con leerClasesSeleccionadas().
    ];
    $errores = [];

    foreach (['titulo', 'descripcion'] as $campoTexto) {
        if (!mb_check_encoding($valores[$campoTexto], 'UTF-8')) {
            $valores[$campoTexto] = mb_scrub($valores[$campoTexto], 'UTF-8');
            $errores[$campoTexto] = 'El texto contiene caracteres no válidos. Escríbelo de nuevo.';
        }
    }

    if ($valores['titulo'] === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (!isset($errores['titulo']) && mb_strlen($valores['titulo'], 'UTF-8') > 200) {
        $errores['titulo'] = 'El título admite como máximo 200 caracteres.';
    }
    if (!isset($errores['descripcion']) && mb_strlen($valores['descripcion'], 'UTF-8') > 5000) {
        $errores['descripcion'] = 'La descripción admite como máximo 5000 caracteres.';
    }

    $tiempo = filter_var($valores['tiempo_minutos'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => TIEMPO_MAXIMO_MINUTOS]]);
    if ($tiempo === false) {
        $errores['tiempo_minutos'] = 'El tiempo límite debe ser un número entero mayor que cero (máximo '
            . TIEMPO_MAXIMO_MINUTOS . ' minutos).';
    }
    $intentos = filter_var($valores['intentos_permitidos'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => INTENTOS_MAXIMOS]]);
    if ($intentos === false) {
        $errores['intentos_permitidos'] = 'Los intentos permitidos deben ser un número entero mayor que cero (máximo '
            . INTENTOS_MAXIMOS . ').';
    }

    $nota = filter_var(str_replace(',', '.', $valores['nota_minima']), FILTER_VALIDATE_FLOAT);
    if ($nota === false || $nota < 0 || $nota > NOTA_MAXIMA) {
        $errores['nota_minima'] = 'La nota mínima debe ser un número entre 0 y ' . NOTA_MAXIMA . '.';
    }

    $apertura = leerFechaFormulario($valores['apertura']);
    $cierre = leerFechaFormulario($valores['cierre']);
    if ($apertura === null) {
        $errores['apertura'] = 'Indica una fecha y hora de apertura válidas.';
    }
    if ($cierre === null) {
        $errores['cierre'] = 'Indica una fecha y hora de cierre válidas.';
    }
    if ($apertura !== null && $cierre !== null && $cierre <= $apertura) {
        $errores['cierre'] = 'La fecha de cierre debe ser posterior a la de apertura.';
    }

    $datos = $errores ? [] : [
        'titulo' => $valores['titulo'],
        'descripcion' => $valores['descripcion'],
        'tiempo_minutos' => $tiempo,
        'apertura' => $apertura->format('Y-m-d H:i:s'),
        'cierre' => $cierre->format('Y-m-d H:i:s'),
        'intentos_permitidos' => $intentos,
        'nota_minima' => number_format(round($nota, 2), 2, '.', ''),
        'orden_aleatorio' => $valores['orden_aleatorio'] ? 1 : 0,
        'mostrar_retroalimentacion' => $valores['mostrar_retroalimentacion'] ? 1 : 0,
    ];
    return [$valores, $datos, $errores];
}

/** Lista de campos que cambian entre la fila guardada y los datos nuevos (para la auditoría). */
function camposModificados(array $anterior, array $nuevo): array
{
    $normalizar = static function (string $campo, mixed $valor): string {
        return match ($campo) {
            'nota_minima' => number_format((float)$valor, 2, '.', ''),
            'apertura', 'cierre' => date('Y-m-d H:i:s', strtotime((string)$valor)),
            'tiempo_minutos', 'intentos_permitidos', 'orden_aleatorio', 'mostrar_retroalimentacion' => (string)(int)$valor,
            default => (string)$valor,
        };
    };
    $cambios = [];
    foreach ($nuevo as $campo => $valor) {
        $antes = $normalizar($campo, $anterior[$campo]);
        $despues = $normalizar($campo, $valor);
        if ($antes !== $despues) {
            $cambios[$campo] = ['antes' => $antes, 'despues' => $despues];
        }
    }
    return $cambios;
}

function formatoFecha(string $valor): string
{
    return date('d/m/Y H:i', strtotime($valor));
}

// ---------------------------------------------------------------------------
// Asignación a clases
// ---------------------------------------------------------------------------

/** Clases del docente con su número de estudiantes. */
function clasesDelDocente(PDO $db, int $docenteId): array
{
    $consulta = $db->prepare(
        'SELECT c.id, c.nombre, c.color, i.nombre AS institucion,
                (SELECT COUNT(*) FROM clase_estudiantes ce WHERE ce.clase_id = c.id) AS estudiantes
         FROM clases c LEFT JOIN instituciones i ON i.id = c.institucion_id
         WHERE c.docente_id = ? ORDER BY c.nombre'
    );
    $consulta->execute([$docenteId]);
    return $consulta->fetchAll();
}

/** @return int[] */
function idsClasesAsignadas(PDO $db, int $evaluacionId): array
{
    $consulta = $db->prepare('SELECT clase_id FROM evaluacion_clases WHERE evaluacion_id = ? ORDER BY clase_id');
    $consulta->execute([$evaluacionId]);
    return array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Valida las clases marcadas en el formulario: solo se aceptan clases del propio docente.
 * @return array{0: int[], 1: ?string} [ids válidos, mensaje de error]
 */
function leerClasesSeleccionadas(mixed $entrada, array $clasesPropias): array
{
    if ($entrada === null || $entrada === '') {
        return [[], null];
    }
    if (!is_array($entrada)) {
        return [[], 'La selección de clases no es válida.'];
    }
    $permitidas = array_map('intval', array_column($clasesPropias, 'id'));
    $ids = [];
    foreach ($entrada as $valor) {
        $id = leerId($valor);
        if ($id === null || !in_array($id, $permitidas, true)) {
            return [[], 'Una de las clases seleccionadas no existe o no es tuya.'];
        }
        $ids[$id] = $id;
    }
    sort($ids);
    return [array_values($ids), null];
}

/** Reemplaza las clases asignadas a una evaluación. */
function guardarClasesEvaluacion(PDO $db, int $evaluacionId, array $ids): void
{
    $db->prepare('DELETE FROM evaluacion_clases WHERE evaluacion_id = ?')->execute([$evaluacionId]);
    $insertar = $db->prepare('INSERT INTO evaluacion_clases (evaluacion_id, clase_id) VALUES (?, ?)');
    foreach ($ids as $claseId) {
        $insertar->execute([$evaluacionId, $claseId]);
    }
}

// ---------------------------------------------------------------------------
// Vista del estudiante
// ---------------------------------------------------------------------------

/**
 * Evaluaciones que un estudiante puede ver: publicadas y asignadas a alguna clase a la que se unió.
 * Las que aún no abren o ya cerraron se devuelven igual, con estado 'proxima' o 'cerrada'.
 * El estado se calcula con NOW() de MySQL, el mismo reloj con el que se guardan las fechas.
 * Nunca selecciona respuestas correctas: solo datos de la evaluación y del propio estudiante.
 */
function evaluacionesDelEstudiante(PDO $db, int $estudianteId, ?int $soloId = null, ?int $claseId = null): array
{
    $filtroClase = $claseId !== null ? ' AND ec.clase_id = :clase' : '';
    $sql = "SELECT * FROM (
                SELECT e.id, e.titulo, e.descripcion, e.tiempo_minutos, e.apertura, e.cierre,
                       e.intentos_permitidos, e.nota_minima, e.orden_aleatorio, e.mostrar_retroalimentacion,
                       u.nombre AS docente,
                       (SELECT COUNT(*) FROM intentos i
                         WHERE i.evaluacion_id = e.id AND i.estudiante_id = :est1) AS intentos_usados,
                       (SELECT i.id FROM intentos i
                         WHERE i.evaluacion_id = e.id AND i.estudiante_id = :est2 AND i.estado = 'en_curso'
                         ORDER BY i.id DESC LIMIT 1) AS intento_en_curso,
                       (SELECT i.id FROM intentos i
                         WHERE i.evaluacion_id = e.id AND i.estudiante_id = :est3 AND i.estado = 'calificado'
                         ORDER BY i.enviado_en DESC, i.id DESC LIMIT 1) AS ultimo_intento,
                       (SELECT COUNT(*) FROM preguntas p WHERE p.evaluacion_id = e.id) AS total_preguntas,
                       (SELECT GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ')
                          FROM evaluacion_clases ec2
                          JOIN clase_estudiantes ce2 ON ce2.clase_id = ec2.clase_id AND ce2.estudiante_id = :est4
                          JOIN clases c ON c.id = ec2.clase_id
                         WHERE ec2.evaluacion_id = e.id) AS clases,
                       CASE WHEN NOW() < e.apertura THEN 'proxima'
                            WHEN NOW() > e.cierre THEN 'cerrada'
                            ELSE 'abierta' END AS estado
                FROM evaluaciones e
                JOIN usuarios u ON u.id = e.docente_id
                WHERE e.publicada = 1
                  AND EXISTS (
                        SELECT 1 FROM evaluacion_clases ec
                        JOIN clase_estudiantes ce ON ce.clase_id = ec.clase_id
                        WHERE ec.evaluacion_id = e.id AND ce.estudiante_id = :est5{$filtroClase})"
        . ($soloId !== null ? ' AND e.id = :id' : '') . "
            ) visibles
            ORDER BY FIELD(estado, 'abierta', 'proxima', 'cerrada'),
                     CASE WHEN estado = 'cerrada' THEN NULL ELSE cierre END,
                     cierre DESC";
    $consulta = $db->prepare($sql);
    foreach ([':est1', ':est2', ':est3', ':est4', ':est5'] as $marcador) {
        $consulta->bindValue($marcador, $estudianteId, PDO::PARAM_INT);
    }
    if ($claseId !== null) {
        $consulta->bindValue(':clase', $claseId, PDO::PARAM_INT);
    }
    if ($soloId !== null) {
        $consulta->bindValue(':id', $soloId, PDO::PARAM_INT);
    }
    $consulta->execute();
    return $consulta->fetchAll();
}

/** Etiqueta visible y clase CSS de cada estado para el estudiante. */
function estadoParaEstudiante(string $estado): array
{
    return match ($estado) {
        'abierta' => ['Disponible', 'badge-publicada'],
        'proxima' => ['Próximamente', 'badge-proxima'],
        default => ['Cerrada', 'badge-cerrada'],
    };
}

// ---------------------------------------------------------------------------
// Preguntas
// ---------------------------------------------------------------------------

const TIPOS_PREGUNTA = [
    'seleccion_multiple' => 'Selección múltiple',
    'verdadero_falso' => 'Verdadero / falso',
    'respuesta_corta' => 'Respuesta corta',
];

/** Las preguntas no se cambian si ya hay intentos: alteraría notas ya calculadas. */
function evaluacionTieneIntentos(PDO $db, int $evaluacionId): bool
{
    $consulta = $db->prepare('SELECT COUNT(*) FROM intentos WHERE evaluacion_id = ?');
    $consulta->execute([$evaluacionId]);
    return (int)$consulta->fetchColumn() > 0;
}
