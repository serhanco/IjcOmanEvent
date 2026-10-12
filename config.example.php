<?php
// Copy this file to config.php and fill in the values. config.php is gitignored; never commit it.
return [
    // SMTP account used to send mail.
    'smtp_host'   => 'smtp.example.com',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',            // 'tls' (port 587), 'ssl' (port 465) or '' for none
    'smtp_user'   => 'user@example.com',
    'smtp_pass'   => '',

    'mail_from'      => 'noreply@example.com',
    'mail_from_name' => 'Acibadem IJC',

    // Who gets an email for every registration (list of addresses).
    'notify_emails' => ['organizer@example.com'],

    // Also send a confirmation email to the registrant.
    'send_confirmation' => false,

    // Panel login (panel.php). Store a HASH, not the password. Generate it with:
    //   php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
    'panel_user'          => 'admin',
    'panel_password_hash' => '',

    // SQLite file. Best kept outside the web root; the default is protected by data/.htaccess.
    'db_path' => __DIR__ . '/data/registrations.sqlite',
];
