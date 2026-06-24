<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

// ── Konfigurasi Gmail ────────────────────────────────────────────────────────
// Ganti dengan email dan App Password kamu
define('MAIL_FROM',     'rakafarza111@gmail.com');
define('MAIL_FROM_NAME','Pusat Pengaduan Masyarakat Cimuncang');
define('MAIL_PASSWORD', 'avel zsbi ffse sxdp'); // App Password 16 karakter

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