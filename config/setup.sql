-- Jalankan ini sekali di phpMyAdmin atau MySQL CLI
-- untuk setup database & tabel

CREATE DATABASE IF NOT EXISTS puspeci_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE puspeci_db;

CREATE TABLE IF NOT EXISTS pengaduan (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nomor_tiket     VARCHAR(20) NOT NULL UNIQUE,
    nama_pelapor    VARCHAR(100) NOT NULL DEFAULT 'Anonim',
    no_hp           VARCHAR(20) DEFAULT NULL,
    kategori        ENUM('Infrastruktur','Keamanan','Kebersihan','Pelayanan Publik','Lainnya') NOT NULL,
    judul           VARCHAR(200) NOT NULL,
    isi_pengaduan   TEXT NOT NULL,
    foto            VARCHAR(255) DEFAULT NULL,
    status          ENUM('Masuk','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Masuk',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
