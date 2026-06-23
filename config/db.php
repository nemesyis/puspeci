<?php
// Ganti nilai ini sesuai environment kamu
// Lokal (XAMPP/Laragon): host = localhost, user = root, pass = ''
// Hostinger nanti: sesuai info di hPanel > MySQL Databases

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'puspeci_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
