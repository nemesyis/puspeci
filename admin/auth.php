<?php
// Sertakan file ini di setiap halaman admin dengan:
// require_once __DIR__ . '/auth.php';
// (atau sesuaikan path-nya)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/login.php?redirect=1');
    exit;
}
