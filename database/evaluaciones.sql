-- Instalación inicial para un entorno de prueba, MySQL 8 o MariaDB.
-- No importar sobre una instalación existente.
CREATE DATABASE IF NOT EXISTS evaluaciones_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evaluaciones_db;

CREATE TABLE roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    privacidad_aceptada_en DATETIME NULL,
    aviso_privacidad TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_correo (correo),
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB;

CREATE TABLE grupos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    docente_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_grupos_docente (docente_id),
    CONSTRAINT fk_grupos_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE grupo_estudiantes (
    grupo_id BIGINT UNSIGNED NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (grupo_id, estudiante_id),
    KEY idx_grupo_estudiante (estudiante_id),
    CONSTRAINT fk_ge_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_ge_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE evaluaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    docente_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    tiempo_minutos INT UNSIGNED NOT NULL,
    apertura DATETIME NOT NULL,
    cierre DATETIME NOT NULL,
    intentos_permitidos INT UNSIGNED NOT NULL DEFAULT 1,
    nota_minima DECIMAL(5,2) NOT NULL,
    orden_aleatorio TINYINT(1) NOT NULL DEFAULT 0,
    mostrar_retroalimentacion TINYINT(1) NOT NULL DEFAULT 0,
    publicada TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_evaluaciones_docente (docente_id),
    KEY idx_evaluaciones_disponibilidad (publicada, apertura, cierre),
    CONSTRAINT fk_evaluaciones_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE evaluacion_grupos (
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    grupo_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (evaluacion_id, grupo_id),
    KEY idx_eg_grupo (grupo_id),
    CONSTRAINT fk_eg_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id) ON DELETE CASCADE,
    CONSTRAINT fk_eg_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE banco_preguntas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    docente_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('seleccion_multiple', 'verdadero_falso', 'respuesta_corta') NOT NULL,
    enunciado TEXT NOT NULL,
    respuesta_corta TEXT NULL,
    opciones_json JSON NULL,
    puntaje DECIMAL(10,2) NOT NULL,
    retroalimentacion TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_banco_docente (docente_id),
    CONSTRAINT fk_banco_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE preguntas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('seleccion_multiple', 'verdadero_falso', 'respuesta_corta') NOT NULL,
    enunciado TEXT NOT NULL,
    respuesta_corta TEXT NULL,
    puntaje DECIMAL(10,2) NOT NULL,
    retroalimentacion TEXT NULL,
    posicion INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_preguntas_evaluacion (evaluacion_id, posicion),
    CONSTRAINT fk_preguntas_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE opciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta_id BIGINT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    correcta TINYINT(1) NOT NULL DEFAULT 0,
    posicion INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_opciones_pregunta (pregunta_id, posicion),
    CONSTRAINT fk_opciones_pregunta FOREIGN KEY (pregunta_id) REFERENCES preguntas (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE intentos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    numero INT UNSIGNED NOT NULL,
    iniciado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vence_en DATETIME NOT NULL,
    enviado_en DATETIME NULL,
    estado ENUM('en_curso', 'pendiente_revision', 'calificado') NOT NULL DEFAULT 'en_curso',
    nota DECIMAL(5,2) NULL,
    orden_json JSON NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_intento_numero (evaluacion_id, estudiante_id, numero),
    KEY idx_intentos_estudiante (estudiante_id, evaluacion_id),
    CONSTRAINT fk_intentos_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id),
    CONSTRAINT fk_intentos_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

CREATE TABLE respuestas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    intento_id BIGINT UNSIGNED NOT NULL,
    pregunta_id BIGINT UNSIGNED NOT NULL,
    opcion_id BIGINT UNSIGNED NULL,
    texto TEXT NULL,
    puntaje_obtenido DECIMAL(10,2) NULL,
    revisada_en DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_respuesta_intento_pregunta (intento_id, pregunta_id),
    KEY idx_respuestas_pregunta (pregunta_id),
    KEY idx_respuestas_opcion (opcion_id),
    CONSTRAINT fk_respuestas_intento FOREIGN KEY (intento_id) REFERENCES intentos (id) ON DELETE CASCADE,
    CONSTRAINT fk_respuestas_pregunta FOREIGN KEY (pregunta_id) REFERENCES preguntas (id),
    CONSTRAINT fk_respuestas_opcion FOREIGN KEY (opcion_id) REFERENCES opciones (id)
) ENGINE=InnoDB;

CREATE TABLE recuperacion_password (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expira_en DATETIME NOT NULL,
    usado_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recuperacion_token (token_hash),
    KEY idx_recuperacion_usuario (usuario_id),
    CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    evaluacion_id BIGINT UNSIGNED NULL,
    accion VARCHAR(50) NOT NULL,
    detalle TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_evaluacion (evaluacion_id, creado_en),
    KEY idx_auditoria_usuario (usuario_id),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_auditoria_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO roles (nombre) VALUES ('administrador'), ('docente'), ('estudiante');

-- Sólo para pruebas locales: todas las cuentas usan SoloPrueba-2026!.
-- Eliminar o cambiar las contraseñas antes de cualquier despliegue real.
INSERT INTO usuarios (rol_id, nombre, correo, password_hash) VALUES
    (1, 'Administrador de prueba', 'admin@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (2, 'Docente Uno', 'docente1@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (2, 'Docente Dos', 'docente2@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (3, 'Estudiante Uno', 'estudiante1@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (3, 'Estudiante Dos', 'estudiante2@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (3, 'Estudiante Tres', 'estudiante3@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (3, 'Estudiante Cuatro', 'estudiante4@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG'),
    (3, 'Estudiante Cinco', 'estudiante5@example.test', '$2y$10$PQy2Gctq0CWyiD/M0HeofuDDn4rh3BtK2i7tWzdGDKx8OQRibKkNG');
