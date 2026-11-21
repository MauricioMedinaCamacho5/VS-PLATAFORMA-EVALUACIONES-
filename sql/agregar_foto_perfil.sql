-- Migración: agregar foto de perfil a usuarios
-- Para bases de datos existentes

ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER aviso_privacidad;
