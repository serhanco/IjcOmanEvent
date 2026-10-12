<?php
// One-time installer: checks the setup and creates the registrations table.
// Open /event/oman/kur.php?token=<setup_token from config.php>, then DELETE this file from the server.
require __DIR__ . '/common.php';
header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');

try {
    $config = load_config();
} catch (Throwable $e) {
    http_response_code(500);
    exit($e->getMessage());
}

$token = (string)($config['setup_token'] ?? '');
if ($token === '' || !hash_equals($token, (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit("Forbidden. Set setup_token in config.php and open kur.php?token=<that value>.");
}

$ok = true;
$check = function (string $label, bool $pass, string $hint = '') use (&$ok) {
    echo ($pass ? '[ OK ] ' : '[FAIL] ') . $label . ($pass ? '' : " - $hint") . "\n";
    $ok = $ok && $pass;
};

$check('PHP 8.1+', PHP_VERSION_ID >= 80100, 'found ' . PHP_VERSION);
$check('pdo_mysql extension', extension_loaded('pdo_mysql'), 'enable it in the hosting panel');
$check('openssl extension', extension_loaded('openssl'), 'needed for SMTP over TLS');
$check('mbstring extension', extension_loaded('mbstring'));
$check('panel_password_hash set', ($config['panel_password_hash'] ?? '') !== '', 'see config.example.php');
$check('SMTP host/user set', ($config['smtp_host'] ?? '') !== '' && ($config['smtp_user'] ?? '') !== '', 'fill SMTP settings');
$check('notify_emails set', !empty($config['notify_emails']), 'add at least one address');

try {
    $pdo = db($config);
    $check('Database connection', true);
    $pdo->exec(REGISTRATIONS_SCHEMA);
    $check('Table "registrations" ready', true);
} catch (Throwable $e) {
    $check('Database', false, $e->getMessage());
}

echo $ok
    ? "\nAll good. Now DELETE kur.php from the server, then open panel.php.\n"
    : "\nFix the items marked FAIL, then reload this page.\n";
