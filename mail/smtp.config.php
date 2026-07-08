<?php

$localConfigPath = __DIR__ . '/smtp.local.php';

if (is_file($localConfigPath)) {
    return require $localConfigPath;
}

return [
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => getenv('SMTP_USERNAME') ?: '',
    'password' => getenv('SMTP_PASSWORD') ?: '',
    'port' => (int) (getenv('SMTP_PORT') ?: 587),
];
