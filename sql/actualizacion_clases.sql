-- =============================================================================
-- Actualización: clases con código de unión (estilo Google Classroom)
-- Fecha: 2026-11-12 · MySQL 8 o MariaDB 10.4+
--
-- Para bases creadas con la versión anterior de database/evaluaciones.sql.
-- Las instalaciones nuevas no la necesitan: ese archivo ya incluye estas tablas.
--
-- Qué hace:
--   1. Crea instituciones, clases, clase_estudiantes y evaluacion_clases.
--   2. Copia los grupos existentes a clases (sin código: el docente genera uno),
--      sus estudiantes a clase_estudiantes y sus asignaciones a evaluacion_clases.
--   3. Elimina las tablas de grupos y la columna evaluaciones.para_todos
--      (ahora un estudiante solo ve evaluaciones de las clases a las que se unió).
--
-- Haz una copia de seguridad antes de ejecutarlo (phpMyAdmin > Exportar).
-- =============================================================================

USE evaluaciones_db;

-- 1. Tablas nuevas ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS instituciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_instituciones_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clases (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    docente_id BIGINT UNSIGNED NOT NULL,
    institucion_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    color CHAR(7) CHARACTER SET ascii NOT NULL DEFAULT '#1E3A8A',
    -- Código de 6 caracteres para unirse. NULL = sin código generado.
    codigo_union CHAR(6) CHARACTER SET ascii COLLATE ascii_bin NULL,
    codigo_activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clases_codigo (codigo_union),
    KEY idx_clases_docente (docente_id),
    KEY idx_clases_institucion (institucion_id),
    CONSTRAINT fk_clases_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id),
    CONSTRAINT fk_clases_institucion FOREIGN KEY (institucion_id) REFERENCES instituciones (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clase_estudiantes (
    clase_id BIGINT UNSIGNED NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    unido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (clase_id, estudiante_id),
    KEY idx_ce_estudiante (estudiante_id),
    CONSTRAINT fk_ce_clase FOREIGN KEY (clase_id) REFERENCES clases (id) ON DELETE CASCADE,
    CONSTRAINT fk_ce_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluacion_clases (
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    clase_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (evaluacion_id, clase_id),
    KEY idx_ec_clase (clase_id),
    CONSTRAINT fk_ec_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id) ON DELETE CASCADE,
    CONSTRAINT fk_ec_clase FOREIGN KEY (clase_id) REFERENCES clases (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Copiar grupos a clases (solo si las tablas de grupos existen) -------------
-- Cada grupo conserva su id como id de clase, así sus relaciones se copian directamente.

SET @hay_grupos := (SELECT COUNT(*) FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grupos');

SET @sql := IF(@hay_grupos > 0,
    'INSERT IGNORE INTO clases (id, docente_id, nombre, codigo_union, codigo_activo)
     SELECT id, docente_id, nombre, NULL, 0 FROM grupos',
    'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

SET @sql := IF(@hay_grupos > 0,
    'INSERT IGNORE INTO clase_estudiantes (clase_id, estudiante_id)
     SELECT grupo_id, estudiante_id FROM grupo_estudiantes',
    'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

SET @sql := IF(@hay_grupos > 0,
    'INSERT IGNORE INTO evaluacion_clases (evaluacion_id, clase_id)
     SELECT evaluacion_id, grupo_id FROM evaluacion_grupos',
    'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- 3. Retirar lo anterior --------------------------------------------------------

DROP TABLE IF EXISTS evaluacion_grupos;
DROP TABLE IF EXISTS grupo_estudiantes;
DROP TABLE IF EXISTS grupos;

-- evaluaciones.para_todos solo existe si se aplicó la migración anterior.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluaciones' AND COLUMN_NAME = 'para_todos') > 0,
    'ALTER TABLE evaluaciones DROP COLUMN para_todos',
    'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- Comprobación: debe mostrar las 4 tablas nuevas.
SELECT TABLE_NAME AS tabla_creada FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('instituciones', 'clases', 'clase_estudiantes', 'evaluacion_clases');
