<?php
/**
 * TSACI Admin - Outgoing Mail Configuration (PHPMailer / Gmail SMTP)
 *
 * Setup (Gmail):
 *   1. Enable 2-Step Verification on the Gmail account.
 *   2. Create an App Password: Google Account > Security > App passwords.
 *   3. Paste the 16-character app password below (spaces optional).
 *
 * NOTE: keep this file out of version control. If the repo is committed
 * publicly, move credentials to environment variables instead.
 */

// Master switch — set to true once credentials below are filled in.
define('SMTP_ENABLED', true);

// Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' (STARTTLS, port 587) or 'ssl' (port 465)

// Credentials — stored in admin/mail.ini (denied web access via .htaccess)
$mail_ini = __DIR__ . '/mail.ini';
$mail_creds = (file_exists($mail_ini) ? parse_ini_file($mail_ini) : []) ?: [];
define('SMTP_USERNAME', $mail_creds['username'] ?? '');
define('SMTP_PASSWORD', $mail_creds['password'] ?? '');

// Sender identity (what recipients see in From:)
define('SMTP_FROM_EMAIL', '');  // usually same as SMTP_USERNAME
define('SMTP_FROM_NAME', 'TSACI Support');
