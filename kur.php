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

// Helper: turn the panel password you choose into the hash that config.php needs.
if (isset($_POST['make_hash'])) {
    $pw = (string)($_POST['password'] ?? '');
    echo $pw === ''
        ? "Password was empty.\n"
        : "Copy this into panel_password_hash in config.php:\n\n" . password_hash($pw, PASSWORD_DEFAULT) . "\n";
    exit;
}
if (isset($_GET['hash'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><meta charset="UTF-8"><title>Panel password hash</title>'
        . '<form method="post" action="?token=' . h((string)$_GET['token']) . '" style="font-family:sans-serif;max-width:32rem">'
        . '<p>Type the panel password you want:</p>'
        . '<input type="hidden" name="make_hash" value="1">'
        . '<input name="password" type="text" autocomplete="off" style="width:100%;padding:.5rem">'
        . '<p><button>Make hash</button></p></form>';
    exit;
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
$check('panel_password set', ($config['panel_password'] ?? '') !== '' || ($config['panel_password_hash'] ?? '') !== '', 'set panel_password in config.php');
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
