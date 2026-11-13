<?php
declare(strict_types=1);

// Cargar la configuración
$config = require __DIR__ . '/../config/config.php';

try {
    // Crear conexión PDO
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['name']
    );

    $pdo = new PDO(
        $dsn,
        $config['db']['user'],
        $config['db']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "✅ ¡Conexión exitosa a la base de datos!<br>";
    echo "Base de datos: " . $config['db']['name'] . "<br>";
    
    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<br>📊 Tablas encontradas: " . count($tables) . "<br>";
    echo implode(", ", $tables);
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>
