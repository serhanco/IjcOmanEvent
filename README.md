# Modern Arthroplasty Conference, Oman: landing page

Single page site published at http://acibademijc.com/event/oman. Plain PHP, no framework.

- `index.html`: the page. The form posts to `kayit.php`.
- `kayit.php`: validates, stores the registration in SQLite, emails the organizers (PHPMailer over SMTP).
- `panel.php`: password protected list of registrations with search, country filter and CSV download.
- `config.example.php`: template for all settings and secrets.
- `lib/PHPMailer/`: bundled PHPMailer (LGPL), so no Composer is needed on the host.

## Setup

Requires PHP 8.1+ with `pdo_sqlite`, `openssl` and `mbstring` (all standard).

1. Upload everything to the `/event/oman` folder.
2. Copy `config.example.php` to `config.php` and fill in the SMTP account, `notify_emails` and the panel login.
   `config.php` is gitignored and must never be committed.
3. Create the panel password hash and paste it as `panel_password_hash`:
   `php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"`
4. Make sure `data/` is writable by the web server. It is closed to the web by `data/.htaccess` (Apache).
   On nginx, deny `/event/oman/data/` yourself, or point `db_path` to a folder outside the web root (recommended).
5. Open `/event/oman/panel.php`, submit a test registration from the page and check the mail and the panel.

## Notes

- Registered countries: Saudi Arabia, Bahrain, Qatar, Kuwait, UAE (checked on the server too).
- A mail failure never loses a registration; it is saved first and the error is written to the PHP error log.
- Spam protection: hidden honeypot field. Add a CAPTCHA if abuse appears.
- `index.html` links to `Javad_Parvizi_Oman_Conference_A5_compressed.pdf`; place that file next to `index.html`.
- Registrations are personal data: use HTTPS for the panel and keep the panel password private.
