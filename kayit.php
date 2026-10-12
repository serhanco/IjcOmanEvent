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
    $pdo->prepare('INSERT INTO registrations (created_at, full_name, email, phone, institution, country)
                   VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([gmdate('Y-m-d H:i:s'), $reg['full_name'], $reg['email'], $reg['phone'], $reg['institution'], $reg['country']]);
} catch (Throwable $e) {
    error_log('registration store failed: ' . $e->getMessage());
    back('hata');
}

// The registration is already saved; a mail failure must not make the visitor retry.
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->Port = (int)$config['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_user'];
    $mail->Password = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['mail_from'], $config['mail_from_name']);
    foreach ($config['notify_emails'] as $to) {
        $mail->addAddress($to);
    }
    $mail->addReplyTo($reg['email'], $reg['full_name']);
    $mail->Subject = 'New registration: ' . $reg['full_name'] . ' (' . $reg['institution'] . ')';
    $mail->isHTML(true);
    $mail->Body = '<p>New registration request for the Modern Arthroplasty Conference (Oman).</p><table cellpadding="6" border="1" style="border-collapse:collapse">'
        . '<tr><td><b>Full Name</b></td><td>' . h($reg['full_name']) . '</td></tr>'
        . '<tr><td><b>Email</b></td><td>' . h($reg['email']) . '</td></tr>'
        . '<tr><td><b>Phone</b></td><td>' . h($reg['phone']) . '</td></tr>'
        . '<tr><td><b>Institution</b></td><td>' . h($reg['institution']) . '</td></tr>'
        . '<tr><td><b>Country</b></td><td>' . h($reg['country']) . '</td></tr></table>';
    $mail->AltBody = "Full Name: {$reg['full_name']}\nEmail: {$reg['email']}\nPhone: {$reg['phone']}\n"
        . "Institution: {$reg['institution']}\nCountry: {$reg['country']}";
    $mail->send();

    if (!empty($config['send_confirmation'])) {
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->addAddress($reg['email'], $reg['full_name']);
        $mail->Subject = 'Your registration request: Modern Arthroplasty Conference, Oman';
        $mail->isHTML(false);
        $mail->Body = "Dear {$reg['full_name']},\n\nThank you for your interest in the Modern Arthroplasty Conference "
            . "(December 11-12, 2026, Oman). We have received your registration request and will contact you shortly.\n\n"
            . $config['mail_from_name'];
        $mail->send();
    }
} catch (Throwable $e) {
    error_log('registration mail failed: ' . $e->getMessage());
}

back('ok');
