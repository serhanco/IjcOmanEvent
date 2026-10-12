<?php
// Copy this file to config.php and fill in the values. config.php is gitignored; never commit it.
return [
    // SMTP account used to send mail.
    'smtp_host'   => 'mail.kurumsaleposta.com',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',            // 'tls' (port 587), 'ssl' (port 465) or '' for none
    'smtp_user'   => 'web@internationalapp.net',
    'smtp_pass'   => '',               // fill in on the server, never commit it

    'mail_from'      => 'web@internationalapp.net',
    'mail_from_name' => 'Web Form',

    // Who gets an email for every registration (list of addresses).
    'notify_emails' => ['organizer@example.com'],
    // Optional copies. Same format: ['a@example.com', 'b@example.com'] or 'a@example.com, b@example.com'.
    'notify_cc' => [],

    // Also send a confirmation email to the registrant.
    'send_confirmation' => false,

    // Panel login (panel.php). Pick any username and password.
    'panel_user'     => 'admin',
    'panel_password' => '',

    // MySQL / MariaDB database (create the empty database and user in your hosting panel first).
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',

    // Optional. If set, kur.php needs ?token=<this value>. Leave empty to skip. Any long random string, e.g.:
    //   php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
    // Delete kur.php from the server after installing.
    'setup_token' => '',
];
