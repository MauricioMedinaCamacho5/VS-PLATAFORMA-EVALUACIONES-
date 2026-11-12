<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
$usuario = requerirRol('administrador');
$titulo = 'Panel de administrador';
$descripcion = 'Usa Gestionar roles en el menú para cambiar el rol de una cuenta.';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
