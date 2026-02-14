<?php
/**
 * Database connection. Credentials via config.php (gitignored) or env vars.
 * Copy config.example.php to config.php and set DB_HOST, DB_NAME, DB_USER, DB_PASS.
 */
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

function getDbConnection(): ?PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: '';
    $username = getenv('DB_USER') ?: '';
    $password = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}
