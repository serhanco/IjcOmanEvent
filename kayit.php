<?php
// Receives the registration form: validates, stores in SQLite, emails the organizers.
require __DIR__ . '/common.php';
require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function back(string $status): never {
    header('Location: ./?kayit=' . $status . '#register');
    exit;
}

function field(string $name): string {
    $v = preg_replace('/\s+/u', ' ', (string)($_POST[$name] ?? ''));
    return mb_substr(trim($v), 0, 200);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('hata');
}

// Honeypot: real visitors never fill this hidden field. Pretend success so bots move on.
if (!empty($_POST['website'])) {
    back('ok');
}

$reg = [
    'full_name'   => field('fullName'),
    'email'       => field('email'),
    'phone'       => field('phone'),
    'institution' => field('institution'),
    'country'     => field('country'),
];

if (in_array('', $reg, true) || ($_POST['consent'] ?? '') !== 'yes'
    || !filter_var($reg['email'], FILTER_VALIDATE_EMAIL)
    || !in_array($reg['country'], COUNTRIES, true)) {
    back('hata');
}

try {
    $config = load_config();
    $pdo = db($config);
    ensure_schema($pdo);
    $pdo->prepare('INSERT INTO registrations (created_at, full_name, email, phone, institution, country)
                   VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([gmdate('Y-m-d H:i:s'), $reg['full_name'], $reg['email'], $reg['phone'], $reg['institution'], $reg['country']]);
    $id = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    error_log('registration store failed: ' . $e->getMessage());
    back('hata');
}

// The registration is already saved; a mail failure must not make the visitor retry.
// The error is stored with the registration so it shows up in panel.php.
$mailError = '';
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->Port = (int)$config['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_user'];
    $mail->Password = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->SMTPAutoTLS = $config['smtp_autotls'] ?? true;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['mail_from'], $config['mail_from_name']);
    foreach (addresses($config['notify_emails'] ?? []) as $to) {
        $mail->addAddress($to);
    }
    foreach (addresses($config['notify_cc'] ?? []) as $cc) {
        $mail->addCC($cc);
    }
    $mail->addReplyTo($reg['email'], $reg['full_name']);
    $mail->Subject = 'New registration: ' . $reg['full_name'] . ' (' . $reg['institution'] . ')';
    $mail->isHTML(true);
    $siteUrl = $config['site_url'] ?? 'http://acibademijc.com/event/oman/';
    $mail->Body = mail_layout($siteUrl, 'New registration request', 'A new registration request was submitted on the event page.', [
        ['Full Name', $reg['full_name']],
        ['Email', $reg['email']],
        ['Phone', $reg['phone']],
        ['Institution', $reg['institution']],
        ['Country', $reg['country']],
        ['Received (UTC)', gmdate('Y-m-d H:i')],
    ], 'Reply to this email to answer the registrant directly.');
    $mail->AltBody = "Full Name: {$reg['full_name']}\nEmail: {$reg['email']}\nPhone: {$reg['phone']}\n"
        . "Institution: {$reg['institution']}\nCountry: {$reg['country']}";
    $mail->send();

    if (!empty($config['send_confirmation'])) {
        $mail->clearAddresses();
        $mail->clearCCs();
        $mail->clearReplyTos();
        $mail->addAddress($reg['email'], $reg['full_name']);
        $mail->Subject = 'Your registration request: Modern Arthroplasty Conference, Oman';
        $mail->Body = mail_layout($siteUrl, 'Registration request received', "Dear {$reg['full_name']},\n\nThank you for your interest in the Modern Arthroplasty Conference. We have received your registration request.", [
            ['Event', 'Modern Arthroplasty: Issues We Face in the Middle East'],
            ['Date', 'December 11-12, 2026'],
            ['Location', 'Oman'],
        ], 'We will review your request and contact you shortly with further details.');
        $mail->AltBody = "Dear {$reg['full_name']},\n\nThank you for your interest in the Modern Arthroplasty Conference "
            . "(December 11-12, 2026, Oman). We have received your registration request and will contact you shortly.";
        $mail->send();
    }
} catch (Throwable $e) {
    error_log('registration mail failed: ' . $e->getMessage());
    $mailError = mb_substr($e->getMessage(), 0, 500);
}

try {
    $pdo->prepare('UPDATE registrations SET mail_error = ? WHERE id = ?')->execute([$mailError, $id]);
} catch (Throwable $e) {
    error_log('could not store mail status: ' . $e->getMessage());
}

back('ok');
