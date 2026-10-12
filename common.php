<?php
// Shared helpers for kayit.php and panel.php.

const COUNTRIES = ['Saudi Arabia', 'Bahrain', 'Qatar', 'Kuwait', 'United Arab Emirates'];

function load_config(): array {
    $file = __DIR__ . '/config.php';
    if (!is_file($file)) {
        throw new RuntimeException('config.php is missing. Copy config.example.php to config.php.');
    }
    return require $file;
}

function db(array $config): PDO {
    $dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';
    return new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

const REGISTRATIONS_SCHEMA = 'CREATE TABLE IF NOT EXISTS registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    phone VARCHAR(200) NOT NULL,
    institution VARCHAR(200) NOT NULL,
    country VARCHAR(100) NOT NULL,
    INDEX (country),
    INDEX (created_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
