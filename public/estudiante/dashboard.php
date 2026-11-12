<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
$usuario = requerirRol('estudiante');
$titulo = 'Panel de estudiante';
$descripcion = 'Desde aquí podrás consultar tus evaluaciones y notas cuando los módulos estén disponibles.';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
