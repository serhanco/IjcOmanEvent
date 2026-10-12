# Modern Arthroplasty Conference, Oman: landing page

Single page site published at http://acibademijc.com/event/oman. Plain PHP, no framework.

- `index.html`: the page. The form posts to `kayit.php`.
- `kayit.php`: validates, stores the registration in MySQL, emails the organizers (PHPMailer over SMTP).
- `panel.php`: password protected list of registrations with search, country filter and CSV download.
- `kur.php`: one-time installer (checks the setup, creates the table). Delete it after use.
- `config.example.php`: template for all settings and secrets.
- `lib/PHPMailer/`: bundled PHPMailer (LGPL), so no Composer is needed on the host.

## Setup

Requires PHP 8.1+ with `pdo_mysql`, `openssl` and `mbstring`, and a MySQL/MariaDB database.

1. In the hosting panel create an empty database and a user with access to it.
2. Upload everything to the `/event/oman` folder.
3. Copy `config.example.php` to `config.php` and fill in: database (`db_host`, `db_name`, `db_user`, `db_pass`), SMTP account,
   `notify_emails`, the panel login and a `setup_token`. `config.php` is gitignored and must never be committed.
   Panel login: set `panel_user` and a plain `panel_password` (config.php stays on the server only). Older `panel_password_hash` still works.
4. Open `/event/oman/kur.php?token=<setup_token>`. It lists what is OK or missing and creates the table.
5. **Delete `kur.php` from the server.**
6. Open `/event/oman/panel.php`, submit a test registration from the page and check the mail and the panel.

## Notes

- Registered countries: Saudi Arabia, Bahrain, Qatar, Kuwait, UAE (checked on the server too).
- A mail failure never loses a registration; it is saved first and the error is written to the PHP error log.
- Spam protection: hidden honeypot field. Add a CAPTCHA if abuse appears.
- `index.html` links to `Javad_Parvizi_Oman_Conference_A5_compressed.pdf`; place that file next to `index.html`.
- Registrations are personal data: use HTTPS for the panel and keep the panel password private.
