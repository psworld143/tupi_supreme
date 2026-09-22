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

// Credentials
define('SMTP_USERNAME', 'wh1ssschool@gmail.com');    // e.g. tsaci.sales@gmail.com
define('SMTP_PASSWORD', 'wbla djfk naes bctg');    // 16-char Gmail app password

// Sender identity (what recipients see in From:)
define('SMTP_FROM_EMAIL', '');  // usually same as SMTP_USERNAME
define('SMTP_FROM_NAME', 'TSACI Support');
