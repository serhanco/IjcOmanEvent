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
    $pdo = new PDO('sqlite:' . $config['db_path']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS registrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at TEXT NOT NULL,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT NOT NULL,
        institution TEXT NOT NULL,
        country TEXT NOT NULL
    )');
    return $pdo;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
