<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$databaseConfigPath = __DIR__ . '/db.local.php';
$databaseConfig = [];

if (is_file($databaseConfigPath)) {
    $databaseConfig = require $databaseConfigPath;
} else {
    $databaseConfig = [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_NAME') ?: '',
        'username' => getenv('DB_USERNAME') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ];
}

$pdo = new PDO(
    'mysql:host=' . ($databaseConfig['host'] ?? 'localhost') . ';dbname=' . ($databaseConfig['dbname'] ?? '') . ';charset=utf8',
    $databaseConfig['username'] ?? '',
    $databaseConfig['password'] ?? ''
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!function_exists('ensure_user_schema')) {
    function ensure_user_schema(PDO $pdo): void
    {
        try {
            $pdo->exec("ALTER TABLE Users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'standard_user'");
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE Users ADD COLUMN last_login_at DATETIME NULL");
        } catch (Throwable $e) {
        }
    }
}

ensure_user_schema($pdo);
