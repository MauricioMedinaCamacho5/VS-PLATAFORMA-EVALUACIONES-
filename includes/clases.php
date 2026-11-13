<?php
declare(strict_types=1);

// Clases con código de unión: colores, códigos, instituciones y unión de estudiantes.

require_once __DIR__ . DIRECTORY_SEPARATOR . 'evaluaciones.php';

/** Colores permitidos para las clases (todos con contraste AA para texto blanco). */
const COLORES_CLASE = [
    '#1E3A8A' => 'Azul',
    '#047857' => 'Verde',
    '#6D28D9' => 'Morado',
    '#BE185D' => 'Rosa',
    '#C2410C' => 'Naranja',
    '#0F766E' => 'Turquesa',
    '#B91C1C' => 'Rojo',
    '#334155' => 'Gris',
];

/** Sin 0/O ni 1/I/L para que el código se pueda dictar o copiar a mano sin confusiones. */
const ALFABETO_CODIGO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
const MINUTOS_UNION_PENDIENTE = 30;

function generarCodigoClase(): string
{
    $codigo = '';
    $ultimo = strlen(ALFABETO_CODIGO) - 1;
    for ($i = 0; $i < 6; $i++) {
        $codigo .= ALFABETO_CODIGO[random_int(0, $ultimo)];
    }
    return $codigo;
}

/** Normaliza lo que escribe el estudiante: mayúsculas, sin espacios ni guiones. Null si no es válido. */
function normalizarCodigo(mixed $valor): ?string
{
    if (!is_string($valor)) {
        return null;
    }
    $codigo = strtoupper(preg_replace('/[\s\-]+/', '', $valor) ?? '');
    return preg_match('/^[A-Z0-9]{6}$/', $codigo) === 1 ? $codigo : null;
}

/** Asigna un código nuevo a la clase, reintentando si coincide con uno existente. */
function asignarCodigoNuevo(PDO $db, int $claseId): string
{
    $actualizar = $db->prepare('UPDATE clases SET codigo_union = ?, codigo_activo = 1 WHERE id = ?');
    for ($intento = 0; $intento < 8; $intento++) {
        $codigo = generarCodigoClase();
        try {
            $actualizar->execute([$codigo, $claseId]);
            return $codigo;
        } catch (PDOException $exception) {
            $duplicado = $exception->getCode() === '23000' && (int)($exception->errorInfo[1] ?? 0) === 1062;
            if (!$duplicado) {
                throw $exception;
            }
        }
    }
    throw new RuntimeException('No se pudo generar un código de clase único.');
}

/** Devuelve el id de la institución con ese nombre, creándola si no existe. Null si el nombre está vacío. */
function idInstitucion(PDO $db, string $nombre): ?int
{
    $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? '');
    if ($nombre === '') {
        return null;
    }
    $db->prepare('INSERT IGNORE INTO instituciones (nombre) VALUES (?)')->execute([$nombre]);
    $consulta = $db->prepare('SELECT id FROM instituciones WHERE nombre = ?');
    $consulta->execute([$nombre]);
    return (int)$consulta->fetchColumn();
}

function nombresInstituciones(PDO $db): array
{
    return $db->query('SELECT nombre FROM instituciones ORDER BY nombre')->fetchAll(PDO::FETCH_COLUMN);
}

/** Clase del docente (o null si no existe o es de otro docente). */
function buscarClasePropia(PDO $db, int $claseId, int $docenteId, bool $bloquear = false): ?array
{
    $consulta = $db->prepare(
        'SELECT c.*, i.nombre AS institucion FROM clases c
         LEFT JOIN instituciones i ON i.id = c.institucion_id
         WHERE c.id = ? AND c.docente_id = ?' . ($bloquear ? ' FOR UPDATE' : '')
    );
    $consulta->execute([$claseId, $docenteId]);
    $fila = $consulta->fetch();
    return $fila === false ? null : $fila;
}

/** Clase a la que pertenece el estudiante (o null). */
function buscarClaseDelEstudiante(PDO $db, int $claseId, int $estudianteId): ?array
{
    $consulta = $db->prepare(
        'SELECT c.id, c.nombre, c.descripcion, c.color, i.nombre AS institucion, u.nombre AS docente, ce.unido_en
         FROM clase_estudiantes ce
         JOIN clases c ON c.id = ce.clase_id
         JOIN usuarios u ON u.id = c.docente_id
         LEFT JOIN instituciones i ON i.id = c.institucion_id
         WHERE ce.clase_id = ? AND ce.estudiante_id = ?'
    );
    $consulta->execute([$claseId, $estudianteId]);
    $fila = $consulta->fetch();
    return $fila === false ? null : $fila;
}

/** Clase con ese código activo, con datos públicos para mostrar antes de unirse. */
function claseActivaPorCodigo(PDO $db, string $codigo): ?array
{
    $consulta = $db->prepare(
        'SELECT c.id, c.nombre, c.color, i.nombre AS institucion, u.nombre AS docente
         FROM clases c JOIN usuarios u ON u.id = c.docente_id
         LEFT JOIN instituciones i ON i.id = c.institucion_id
         WHERE c.codigo_union = ? AND c.codigo_activo = 1 AND u.activo = 1'
    );
    $consulta->execute([$codigo]);
    $fila = $consulta->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Une al estudiante a la clase del código.
 * @return array{ok: bool, mensaje: string, tipo: string, clase: ?array}
 */
function unirEstudianteAClase(PDO $db, int $estudianteId, mixed $codigoRecibido): array
{
    $codigo = normalizarCodigo($codigoRecibido);
    if ($codigo === null) {
        return ['ok' => false, 'mensaje' => 'El código debe tener 6 letras o números, por ejemplo AB12CD.', 'tipo' => 'danger', 'clase' => null];
    }
    $clase = claseActivaPorCodigo($db, $codigo);
    if ($clase === null) {
        return ['ok' => false, 'mensaje' => 'Ese código no existe o el docente lo desactivó. Pídele uno nuevo.', 'tipo' => 'danger', 'clase' => null];
    }
    $insertar = $db->prepare('INSERT IGNORE INTO clase_estudiantes (clase_id, estudiante_id) VALUES (?, ?)');
    $insertar->execute([$clase['id'], $estudianteId]);
    if ($insertar->rowCount() === 0) {
        return ['ok' => true, 'mensaje' => 'Ya formabas parte de «' . $clase['nombre'] . '».', 'tipo' => 'info', 'clase' => $clase];
    }
    registrarAuditoria($db, $estudianteId, null, 'clase_unirse', ['clase_id' => (int)$clase['id'], 'codigo' => $codigo]);
    return ['ok' => true, 'mensaje' => 'Te uniste a «' . $clase['nombre'] . '».', 'tipo' => 'success', 'clase' => $clase];
}

// ---- Unión pendiente: enlace abierto sin sesión ----------------------------

function guardarUnionPendiente(string $codigo): void
{
    $_SESSION['union_pendiente'] = ['codigo' => $codigo, 'hasta' => time() + MINUTOS_UNION_PENDIENTE * 60];
}

/** Código pendiente vigente (o null). No lo consume. */
function codigoUnionPendiente(): ?string
{
    $pendiente = $_SESSION['union_pendiente'] ?? null;
    if (!is_array($pendiente) || ($pendiente['hasta'] ?? 0) < time()) {
        unset($_SESSION['union_pendiente']);
        return null;
    }
    return normalizarCodigo($pendiente['codigo'] ?? null);
}

/**
 * Se llama justo después de iniciar sesión o registrarse. Si había un enlace de unión pendiente,
 * une al estudiante y devuelve el panel al que debe ir; para otros roles solo avisa.
 */
function procesarUnionPendiente(PDO $db, array $usuario): ?string
{
    $codigo = codigoUnionPendiente();
    unset($_SESSION['union_pendiente']);
    if ($codigo === null) {
        return null;
    }
    if ($usuario['rol'] !== 'estudiante') {
        $_SESSION['mensaje_evaluaciones'] = ['texto' => 'Solo las cuentas de estudiante pueden unirse a una clase con código.', 'tipo' => 'warning'];
        return null;
    }
    $resultado = unirEstudianteAClase($db, (int)$usuario['id'], $codigo);
    $_SESSION['mensaje_evaluaciones'] = ['texto' => $resultado['mensaje'], 'tipo' => $resultado['tipo']];
    return 'estudiante/clases.php';
}

/** Enlace absoluto para unirse con un código (se muestra al docente para compartirlo). */
function enlaceUnion(string $codigo): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    // Los paneles están en public/<rol>/; unirse.php está en public/.
    $base = rtrim(str_replace('\\', '/', dirname((string)$_SERVER['SCRIPT_NAME'], 2)), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $base . '/unirse.php?codigo=' . rawurlencode($codigo);
}
