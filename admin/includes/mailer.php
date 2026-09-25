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
 * Send a reply to a contact-form message.
 *
 * @return array ['success' => bool, 'error' => string]
 */
function sendMessageReply($toEmail, $toName, $subject, $body) {
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

        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $body; // plain-text body

        $mail->send();
        return ['success' => true, 'error' => ''];
    } catch (PHPMailerException $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
