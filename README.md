# Modern Arthroplasty Conference, Oman: landing page

Single page site (`index.html`) published at http://acibademijc.com/event/oman.
Registrations go to a Google Sheet and trigger an email to the organizers, using a small
Google Apps Script web app (`apps-script/Code.gs`). No server and no secrets in the repo.

## How it works

1. The form in `index.html` POSTs to `window.REGISTRATION_ENDPOINT`.
2. The Apps Script appends a row to the bound Google Sheet and emails `NOTIFY_EMAILS`.
3. Optionally it also sends a confirmation email to the registrant.

The endpoint URL is public by nature (the browser calls it) and is not a secret. Everything
sensitive (recipient addresses, options) lives in Script Properties inside Google, never in git.

## Setup (once)

1. Create a Google Sheet, e.g. "Oman Registrations", with the account that should own the data and send the emails.
2. In the sheet: **Extensions > Apps Script**. Replace the contents with `apps-script/Code.gs`.
   (Optional: paste `apps-script/appsscript.json` via Project Settings > "Show appsscript.json".)
3. **Project Settings > Script Properties**, add:
   - `NOTIFY_EMAILS`: comma separated addresses to notify, e.g. `a@x.com,b@x.com` (required)
   - `SEND_CONFIRMATION`: `true` to email a confirmation to registrants (optional)
   - `SHEET_NAME`: tab name, default `Registrations` (optional)
4. **Deploy > New deployment > Web app**: Execute as *Me*, Who has access *Anyone*. Authorize when prompted.
5. Copy the web app URL (`https://script.google.com/macros/s/.../exec`) into
   `window.REGISTRATION_ENDPOINT` in `index.html` and publish the page.
6. Submit a test registration and check the sheet and inbox.

After changing `Code.gs`, use **Deploy > Manage deployments > Edit > New version** so the URL stays the same.

## Notes

- Gmail accounts can send about 100 emails/day via Apps Script (Workspace about 1500); plenty for a free event registration.
- Spam protection: hidden honeypot field plus server side validation. Add reCAPTCHA/Turnstile later if abuse appears.
- `index.html` links to `Javad_Parvizi_Oman_Conference_A5_compressed.pdf`; place that file next to `index.html` on the host.
- Registrations contain personal data: keep the sheet's sharing restricted to the organizers.
