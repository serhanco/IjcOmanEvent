<?php
// Password protected list of registrations: search, country filter, CSV export.
require __DIR__ . '/common.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$config = load_config();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrfOk = fn() => hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrfOk()) {
    if (($_POST['action'] ?? '') === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: panel.php');
        exit;
    }
    if (($_POST['action'] ?? '') === 'login') {
        $plain = (string)($config['panel_password'] ?? '');
        $hash = (string)($config['panel_password_hash'] ?? '');
        $given = (string)($_POST['pass'] ?? '');
        $userOk = hash_equals((string)$config['panel_user'], (string)($_POST['user'] ?? ''));
        $passOk = $plain !== '' ? hash_equals($plain, $given) : ($hash !== '' && password_verify($given, $hash));
        if ($userOk && $passOk) {
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
            header('Location: panel.php');
            exit;
        }
        sleep(1); // slow down guessing
        $error = 'Wrong username or password.';
    }
}

if (empty($_SESSION['auth'])) {
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel login</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
<form method="post" class="bg-white p-8 rounded-xl shadow w-full max-w-sm space-y-4">
<h1 class="text-xl font-bold text-gray-900">Registrations</h1>
<?php if ($error): ?><p class="text-sm text-red-600"><?= h($error) ?></p><?php endif; ?>
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="login">
<input name="user" placeholder="Username" required autocomplete="username" class="w-full border rounded-lg px-3 py-2">
<input name="pass" type="password" placeholder="Password" required autocomplete="current-password" class="w-full border rounded-lg px-3 py-2">
<button class="w-full bg-blue-800 text-white rounded-lg py-2 font-semibold">Sign in</button>
</form></body></html><?php
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$country = (string)($_GET['country'] ?? '');
$where = [];
$args = [];
if ($q !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR institution LIKE ?)';
    array_push($args, ...array_fill(0, 4, '%' . $q . '%'));
}
if (in_array($country, COUNTRIES, true)) {
    $where[] = 'country = ?';
    $args[] = $country;
}
$pdo = db($config);
ensure_schema($pdo);
$stmt = $pdo->prepare('SELECT * FROM registrations' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC');
$stmt->execute($args);
$rows = $stmt->fetchAll();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="registrations-' . gmdate('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
    fputcsv($out, ['ID', 'Date (UTC)', 'Full Name', 'Email', 'Phone', 'Institution', 'Country', 'Mail error']);
    foreach ($rows as $r) {
        // Prefix risky leading characters so Excel does not run them as formulas.
        $cells = array_map(fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v,
            [$r['full_name'], $r['email'], $r['phone'], $r['institution']]);
        fputcsv($out, [$r['id'], $r['created_at'], ...$cells, $r['country'], $r['mail_error'] ?? '']);
    }
    exit;
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>Registrations</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 p-4 md:p-8"><div class="max-w-6xl mx-auto">
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
<h1 class="text-2xl font-bold text-gray-900">Registrations <span class="text-gray-500 text-lg">(<?= count($rows) ?>)</span></h1>
<form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="logout">
<button class="text-sm text-gray-600 underline">Sign out</button></form></div>
<form method="get" class="flex flex-wrap gap-2 mb-4">
<input name="q" value="<?= h($q) ?>" placeholder="Search name, email, phone, institution" class="border rounded-lg px-3 py-2 flex-1 min-w-[14rem]">
<select name="country" class="border rounded-lg px-3 py-2 bg-white"><option value="">All countries</option>
<?php foreach (COUNTRIES as $c): ?><option <?= $c === $country ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select>
<button class="bg-blue-800 text-white rounded-lg px-4 py-2">Filter</button>
<button name="export" value="1" class="bg-white border rounded-lg px-4 py-2">Download CSV</button></form>
<div class="bg-white rounded-xl shadow overflow-x-auto"><table class="w-full text-sm text-left">
<thead class="bg-gray-50 text-gray-600"><tr><?php foreach (['#', 'Date (UTC)', 'Full Name', 'Email', 'Phone', 'Institution', 'Country', 'Mail'] as $t): ?><th class="px-4 py-3"><?= $t ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr class="border-t">
<td class="px-4 py-3"><?= (int)$r['id'] ?></td><td class="px-4 py-3 whitespace-nowrap"><?= h($r['created_at']) ?></td>
<td class="px-4 py-3"><?= h($r['full_name']) ?></td><td class="px-4 py-3"><?= h($r['email']) ?></td>
<td class="px-4 py-3"><?= h($r['phone']) ?></td><td class="px-4 py-3"><?= h($r['institution']) ?></td><td class="px-4 py-3"><?= h($r['country']) ?></td>
<td class="px-4 py-3"><?php if (($r['mail_error'] ?? '') === ''): ?><span class="text-green-700">OK</span><?php else: ?><span class="text-red-600 text-xs"><?= h($r['mail_error']) ?></span><?php endif; ?></td></tr>
<?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No registrations.</td></tr><?php endif; ?>
</tbody></table></div></div></body></html>
