<?php
declare(strict_types=1);

// Copiar fuera de htdocs, por ejemplo a C:\xamppp\evaluaciones-config\config.php.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'evaluaciones_db',
        'user' => 'root',
        'password' => '',
    ],
    // Debe describir al responsable y las finalidades reales antes de usar el registro.
    'privacy_notice' => 'Al registrarte en la plataforma de evaluaciones, autorizas el tratamiento de tus datos personales (nombre y correo electrónico) para la gestión académica, presentación de pruebas y registro de evaluaciones.',
];
