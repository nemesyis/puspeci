<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

// Load mail credentials from environment for safety. Do NOT keep secrets in
// repository. Set `MAIL_FROM`, `MAIL_FROM_NAME`, and `MAIL_PASSWORD` in the
// environment (or via a .env loader) on the server.
$env_mail_from = getenv('MAIL_FROM') ?: null;
$env_mail_from_name = getenv('MAIL_FROM_NAME') ?: null;
$env_mail_password = getenv('MAIL_PASSWORD') ?: null;

define('MAIL_FROM',     $env_mail_from);
define('MAIL_FROM_NAME', $env_mail_from_name ?: 'Pusat Pengaduan Masyarakat Cimuncang');
define('MAIL_PASSWORD',  $env_mail_password);

/**
 * Kirim email via Gmail SMTP.
 *
 * @param string $to      Alamat email tujuan
 * @param string $subject Subjek email
 * @param string $body    Isi email (plain text)
 * @return bool
 */
function kirim_email(string $to, string $subject, string $body): bool
{
    // Basic validation
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    // Check credentials
    if (empty(MAIL_FROM) || empty(MAIL_PASSWORD)) {
        error_log('kirim_email: mail credentials not configured; email skipped');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error tapi jangan hentikan proses utama
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}