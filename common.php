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
    mail_error TEXT NULL,
    INDEX (country),
    INDEX (created_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

// Accepts a list (['a@x.com', 'b@x.com']) or a comma separated string ('a@x.com, b@x.com').
function addresses($value): array {
    $list = is_array($value) ? $value : explode(',', (string)$value);
    return array_values(array_filter(array_map('trim', $list), fn($a) => $a !== ''));
}

// Creates the table if needed and adds columns introduced after the first install.
function ensure_schema(PDO $pdo): void {
    $pdo->exec(REGISTRATIONS_SCHEMA);
    if (!$pdo->query("SHOW COLUMNS FROM registrations LIKE 'mail_error'")->fetchAll()) {
        $pdo->exec('ALTER TABLE registrations ADD COLUMN mail_error TEXT NULL');
    }
}

// Styled HTML email (tables and inline CSS so it renders in Outlook and Gmail).
// $rows is a list of [label, value] pairs shown as a details table; $intro/$outro are plain text.
function mail_layout(string $siteUrl, string $title, string $intro, array $rows, string $outro = ''): string {
    $logo = h(rtrim($siteUrl, '/') . '/assets/ijc-logo.png');
    $cells = '';
    foreach ($rows as $i => [$label, $value]) {
        $bg = $i % 2 ? '#F3F4F6' : '#FFFFFF';
        $cells .= '<tr><td style="padding:10px 16px;background:' . $bg . ';color:#6B7280;font-size:13px;width:34%;vertical-align:top">' . h($label) . '</td>'
            . '<td style="padding:10px 16px;background:' . $bg . ';color:#111827;font-size:15px;font-weight:600">' . h($value) . '</td></tr>';
    }
    $table = $cells ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;border-collapse:separate;overflow:hidden;margin:20px 0">' . $cells . '</table>' : '';
    $outroHtml = $outro !== '' ? '<p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:#374151">' . nl2br(h($outro)) . '</p>' : '';

    return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,Helvetica,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 12px"><tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E5E7EB">'
        . '<tr><td style="padding:20px 28px;background:#FFFFFF"><img src="' . $logo . '" alt="Acıbadem IJC" height="40" style="display:block;border:0;height:40px"></td></tr>'
        . '<tr><td style="padding:22px 28px;background:#00488F;color:#FFFFFF">'
        . '<div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#BFDBFE">Modern Arthroplasty Conference &middot; Oman</div>'
        . '<div style="font-size:22px;font-weight:700;margin-top:6px">' . h($title) . '</div></td></tr>'
        . '<tr><td style="padding:24px 28px 28px">'
        . '<p style="margin:0;font-size:15px;line-height:1.6;color:#374151">' . nl2br(h($intro)) . '</p>' . $table . $outroHtml . '</td></tr>'
        . '<tr><td style="padding:16px 28px;background:#002E5C;color:#BFDBFE;font-size:12px;text-align:center">December 11-12, 2026 &middot; Oman &middot; Acıbadem International Joint Center</td></tr>'
        . '</table>'
        . '<div style="max-width:600px;padding:12px 0 0;font-size:11px;color:#9CA3AF;text-align:center">Lead Delivery System Created by <a href="https://serhandemirel.com" style="color:#9CA3AF;text-decoration:underline">Serhan Demirel</a></div>'
        . '</td></tr></table></body></html>';
}

// Plain-text counterpart of the HTML footer signature.
const MAIL_SIGNATURE_TEXT = "\n\n--\nLead Delivery System Created by Serhan Demirel (https://serhandemirel.com)";

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
