/**
 * Oman event registration backend (Google Apps Script web app).
 *
 * Bind this script to the Google Sheet that should hold registrations
 * (Extensions > Apps Script inside the sheet), then deploy it as a web app.
 *
 * Configuration lives in Script Properties (Project Settings > Script Properties),
 * never in this file:
 *   NOTIFY_EMAILS      Comma separated list of people who get an email per registration. Required.
 *   SEND_CONFIRMATION  "true" to also send a short confirmation email to the registrant. Optional.
 *   SHEET_NAME         Tab to write into. Optional, defaults to "Registrations".
 */

var HEADERS = ['Timestamp', 'Full Name', 'Email', 'Phone', 'Institution', 'Consent', 'Page URL'];
var MAX_LENGTH = 200;

function doPost(e) {
  try {
    var p = (e && e.parameter) || {};

    // Honeypot: real visitors never fill this hidden field. Pretend success so bots move on.
    if (p.website) {
      return json_({ ok: true });
    }

    var reg = {
      fullName: clean_(p.fullName),
      email: clean_(p.email),
      phone: clean_(p.phone),
      institution: clean_(p.institution),
      consent: p.consent === 'yes' ? 'yes' : '',
      pageUrl: clean_(p.pageUrl)
    };

    if (!reg.fullName || !reg.email || !reg.phone || !reg.institution || !reg.consent) {
      return json_({ ok: false, error: 'missing_fields' });
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(reg.email)) {
      return json_({ ok: false, error: 'invalid_email' });
    }

    appendRow_(reg);
    notify_(reg);

    return json_({ ok: true });
  } catch (err) {
    console.error(err);
    return json_({ ok: false, error: 'server_error' });
  }
}

function doGet() {
  return json_({ ok: true, service: 'oman-registration' });
}

function appendRow_(reg) {
  var props = PropertiesService.getScriptProperties();
  var sheetName = props.getProperty('SHEET_NAME') || 'Registrations';
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  var lock = LockService.getScriptLock();
  lock.waitLock(20000);
  try {
    var sheet = ss.getSheetByName(sheetName) || ss.insertSheet(sheetName);
    if (sheet.getLastRow() === 0) {
      sheet.appendRow(HEADERS);
      sheet.setFrozenRows(1);
    }
    sheet.appendRow([
      new Date(),
      safeCell_(reg.fullName),
      safeCell_(reg.email),
      safeCell_(reg.phone),
      safeCell_(reg.institution),
      reg.consent,
      safeCell_(reg.pageUrl)
    ]);
  } finally {
    lock.releaseLock();
  }
}

function notify_(reg) {
  var props = PropertiesService.getScriptProperties();
  var recipients = (props.getProperty('NOTIFY_EMAILS') || '').trim();
  var sheetUrl = SpreadsheetApp.getActiveSpreadsheet().getUrl();

  if (recipients) {
    var rows = [
      ['Full Name', reg.fullName],
      ['Email', reg.email],
      ['Phone', reg.phone],
      ['Institution', reg.institution]
    ];
    var html = '<p>A new registration request was submitted for the Modern Arthroplasty Conference (Oman).</p>' +
      '<table cellpadding="6" style="border-collapse:collapse">' +
      rows.map(function (r) {
        return '<tr><td style="border:1px solid #ddd"><b>' + r[0] + '</b></td>' +
          '<td style="border:1px solid #ddd">' + escapeHtml_(r[1]) + '</td></tr>';
      }).join('') +
      '</table>' +
      '<p><a href="' + sheetUrl + '">Open all registrations</a></p>';
    var text = rows.map(function (r) { return r[0] + ': ' + r[1]; }).join('\n') +
      '\n\nAll registrations: ' + sheetUrl;

    MailApp.sendEmail({
      to: recipients,
      subject: 'New registration: ' + reg.fullName + ' (' + reg.institution + ')',
      body: text,
      htmlBody: html,
      replyTo: reg.email
    });
  } else {
    console.warn('NOTIFY_EMAILS is not set; skipping notification email.');
  }

  if (props.getProperty('SEND_CONFIRMATION') === 'true') {
    var confirmation = {
      to: reg.email,
      subject: 'Your registration request: Modern Arthroplasty Conference, Oman',
      body: 'Dear ' + reg.fullName + ',\n\n' +
        'Thank you for your interest in the Modern Arthroplasty Conference (December 11-12, 2026, Oman). ' +
        'We have received your registration request and will contact you shortly with further details.\n\n' +
        'Acıbadem IJC'
    };
    if (recipients) {
      confirmation.replyTo = recipients.split(',')[0].trim();
    }
    MailApp.sendEmail(confirmation);
  }
}

function clean_(value) {
  return String(value || '').replace(/[\r\n\t]+/g, ' ').trim().slice(0, MAX_LENGTH);
}

// Stop values like "=HYPERLINK(...)" from being evaluated as formulas in the sheet.
function safeCell_(value) {
  return /^[=+\-@]/.test(value) ? "'" + value : value;
}

function escapeHtml_(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function json_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
