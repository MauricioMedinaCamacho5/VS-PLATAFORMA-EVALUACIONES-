# Perfil de Usuario y Foto de Perfil

## Descripción

Se agregó funcionalidad para que cualquier usuario (administrador, docente o estudiante) pueda editar su perfil y subir una foto de perfil desde dentro de los paneles.

## Cambios Realizados

### Base de Datos

1. **database/evaluaciones.sql** - Agregada columna `foto_perfil` a la tabla `usuarios`:
   ```sql
   ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL;
   ```

2. **sql/agregar_foto_perfil.sql** - Script de migración para bases de datos existentes

### Código

1. **app/bootstrap.php**
   - Agregada función `verificarCsrf(string $token): bool` para verificar tokens CSRF en formularios POST

2. **app/dashboard.php**
   - Agregado botón de perfil en la barra superior (topbar) con icono de persona
   - Permite acceso rápido a `/perfil.php` desde cualquier rol

3. **app/vistas/perfil_vista.php** (nuevo)
   - Vista con formulario para editar nombre
   - Visualización y gestión de foto de perfil
   - Interfaz responsive con diseño acorde a la plataforma

4. **public/docente/perfil.php** (nuevo)
   - Controlador principal para la gestión de perfil
   - Maneja subida, eliminación y actualización de foto de perfil
   - Validación de archivo (JPG, PNG, WebP, máximo 2 MB)
   - Gestión de CSRF
   - Almacena fotos en `/public/fotos_perfil/`

5. **public/admin/perfil.php** y **public/estudiante/perfil.php** (nuevo)
   - Redirigen a la versión docente para código compartido entre roles

### Características

- **Editar nombre**: Campo validado con límite de 2-150 caracteres
- **Subir foto**: 
  - Formatos soportados: JPG, PNG, WebP
  - Tamaño máximo: 2 MB
  - Se elimina automáticamente la foto anterior
  - Nombre único por usuario y timestamp
- **Eliminar foto**: Botón para remover la foto de perfil
  - Se borra del disco cuando se elimina
- **Seguridad**: 
  - Validación de CSRF
  - Solo usuarios autenticados pueden acceder
  - Solo editables los datos propios

## Acceso

- Para administrador: `/admin/perfil.php`
- Para docente: `/docente/perfil.php`
- Para estudiante: `/estudiante/perfil.php`
- Botón rápido en la barra superior de cualquier panel

## Instalación en BD Existente

Ejecutar en MySQL:
```sql
ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL;
```

O usar el script SQL:
```
sql/agregar_foto_perfil.sql
```

## Notas Técnicas

- Las fotos se almacenan en carpetas privadas (`/public/fotos_perfil/`)
- Rutas guardadas en BD son relativas: `fotos_perfil/perfil_1_1700000000.jpg`
- Se usa `object-fit: cover` para mantener proporciones en visualización
- El avatar por defecto usa iniciales del nombre con gradiente de color
