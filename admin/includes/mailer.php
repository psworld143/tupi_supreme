<?php
/**
 * TSACI Admin - Outgoing mail helper (PHPMailer over Gmail SMTP).
 * Requires constants from admin/mail_config.php.
 */

require_once __DIR__ . '/../mail_config.php';

require_once __DIR__ . '/../../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Whether SMTP replies are configured and usable.
 */
function smtpRepliesEnabled() {
    return defined('SMTP_ENABLED') && SMTP_ENABLED
        && defined('SMTP_USERNAME') && SMTP_USERNAME !== ''
        && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '';
}

/**
 * Fetch company contact details for the email footer strip.
 * Pulls the first active email / phone / address rows from contact_info.
 * Fails soft — returns an empty array when the DB is unavailable.
 */
function getMailContactDetails() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = ['email' => '', 'phone' => '', 'address' => ''];
    try {
        $db = getDB();
        if (!$db) return $cache;
        $res = $db->query("SELECT type, value FROM contact_info WHERE is_active = 1 AND type IN ('email','phone','address') ORDER BY display_order");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if ($cache[$row['type']] === '') {
                    // Take only the first line of multi-line values (e.g. address)
                    $cache[$row['type']] = strtok(trim($row['value']), "\n");
                }
            }
        }
    } catch (Throwable $e) { /* fail soft */ }
    return $cache;
}

/**
 * Wrap a plain-text reply in a formal branded HTML template.
 * The body text is authored by an admin (already includes greeting/signature),
 * so it is escaped and inserted verbatim between the letterhead and footer.
 */
function buildFormalEmailHtml($subject, $body, $contextLine = null) {
    if ($contextLine === null) {
        $contextLine = 'This message was sent by %s in response to your inquiry.';
    }
    $company  = defined('SMTP_FROM_NAME') && SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : 'Tupi Supreme Activated Carbon, Inc.';
    $brand    = 'Tupi Supreme';
    $bodyHtml = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
    $subj     = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $year     = date('Y');

    // Contact strip rows (footer)
    $contact = getMailContactDetails();
    $contactRows = '';
    $contactRow = function ($label, $value) {
        if ($value === '') return '';
        return '<tr>'
            . '<td style="padding:3px 0;font-size:11px;font-weight:bold;color:#71717a;text-transform:uppercase;letter-spacing:0.08em;width:64px;vertical-align:top;">' . $label . '</td>'
            . '<td style="padding:3px 0;font-size:12px;color:#52525b;">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    };
    $contactRows .= $contactRow('Email', $contact['email']);
    $contactRows .= $contactRow('Phone', $contact['phone']);
    $contactRows .= $contactRow('Address', $contact['address']);

    return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . $subj . '</title></head>
<body style="margin:0;padding:0;background-color:#eef2ee;font-family:Arial,Helvetica,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;">' . $subj . ' — ' . $brand . '</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ee;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #dfe5df;border-radius:12px;overflow:hidden;">
          <!-- Letterhead -->
          <tr>
            <td style="background-color:#23332c;padding:32px 40px;">
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr>
                <td width="56" valign="middle">
                  <div style="width:52px;height:52px;border-radius:12px;background-color:#8bc34a;color:#23332c;font-family:Georgia,\'Times New Roman\',serif;font-size:20px;font-weight:bold;text-align:center;line-height:52px;letter-spacing:0.04em;">TS</div>
                </td>
                <td style="padding-left:16px;" valign="middle">
                  <div style="font-family:Georgia,\'Times New Roman\',serif;font-size:24px;font-weight:bold;color:#ffffff;letter-spacing:0.02em;line-height:1.2;">' . $brand . '</div>
                  <div style="font-size:10px;color:#a7c1ae;letter-spacing:0.18em;text-transform:uppercase;margin-top:4px;">Activated Carbon</div>
                </td>
                <td valign="middle" align="right">
                  <div style="font-size:10px;color:#a7c1ae;letter-spacing:0.1em;text-transform:uppercase;">' . $year . '</div>
                </td>
              </tr></table>
            </td>
          </tr>
          <tr><td style="height:4px;background-color:#8bc34a;"></td></tr>
          <!-- Subject line -->
          <tr>
            <td style="padding:36px 40px 0;">
              <div style="font-size:10px;font-weight:bold;color:#8bc34a;letter-spacing:0.16em;text-transform:uppercase;margin-bottom:8px;">Official Correspondence</div>
              <div style="font-family:Georgia,\'Times New Roman\',serif;font-size:22px;font-weight:bold;color:#23332c;line-height:1.35;">' . $subj . '</div>
            </td>
          </tr>
          <tr><td style="padding:20px 40px 0;"><hr style="border:0;border-top:1px solid #e4e7e2;margin:0;"></td></tr>
          <!-- Body -->
          <tr>
            <td style="padding:26px 40px 40px;font-size:15px;line-height:1.8;color:#3f3f46;">
              ' . $bodyHtml . '
            </td>
          </tr>
          <!-- Contact strip -->
          ' . ($contactRows !== '' ? '
          <tr>
            <td style="padding:0 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7f4;border-radius:8px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <div style="font-size:10px;font-weight:bold;color:#2c5530;letter-spacing:0.14em;text-transform:uppercase;margin-bottom:8px;">Reach Us</div>
                    <table role="presentation" cellpadding="0" cellspacing="0">' . $contactRows . '</table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          ' : '') . '
          <!-- Legal footer -->
          <tr>
            <td style="padding:24px 40px 32px;">
              <p style="margin:0;font-size:12px;line-height:1.6;color:#71717a;">
                ' . sprintf($contextLine, htmlspecialchars($company, ENT_QUOTES, 'UTF-8')) . '
                If you did not expect this email, please disregard it.
              </p>
              <p style="margin:8px 0 0;font-size:12px;color:#a1a1aa;">&copy; ' . $year . ' ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

/**
 * Send a reply to a contact-form message.
 *
 * @return array ['success' => bool, 'error' => string]
 */
function sendMessageReply($toEmail, $toName, $subject, $body, $contextLine = null) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = str_replace(' ', '', SMTP_PASSWORD);
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $fromEmail = SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : SMTP_USERNAME;
        $mail->setFrom($fromEmail, SMTP_FROM_NAME);
        $mail->addReplyTo($fromEmail, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = buildFormalEmailHtml($subject, $body, $contextLine);
        $mail->AltBody = $body; // plain-text body

        $mail->send();
        return ['success' => true, 'error' => ''];
    } catch (PHPMailerException $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
