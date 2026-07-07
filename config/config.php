<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$pdo = new PDO("mysql:host=localhost;dbname=Multiply_Collective_db;charset=utf8", "mc_admin", "mc_admin");
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
