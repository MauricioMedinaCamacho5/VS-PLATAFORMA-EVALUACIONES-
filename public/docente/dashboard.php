<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth.php';
$usuario = requerirRol('docente');
$titulo = 'Panel de docente';
$descripcion = 'Desde aquí podrás gestionar evaluaciones, preguntas y resultados cuando los módulos estén disponibles.';
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'dashboard.php';
