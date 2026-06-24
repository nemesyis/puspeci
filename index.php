<?php
// Simple session for CSRF token
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// Create a CSRF token for the form if not present
if (empty($_SESSION['csrf'])) {
    try {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Tampilkan pesan sukses/error kalau ada (dikirim dari submit.php via redirect)
$pesan_sukses = $_GET['sukses'] ?? null;
$nomor_tiket  = $_GET['tiket']  ?? null;
$pesan_error  = $_GET['error']  ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Pengaduan Masyarakat Cimuncang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5f3f;
            --primary-light: #e8f5ee;
            --accent: #2e8b57;
        }
        body {
            background: #f4f6f5;
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar-brand span { color: #2e8b57; }
        .hero {
            background: linear-gradient(135deg, #1a5f3f 0%, #2e8b57 100%);
            color: white;
            padding: 48px 0 36px;
        }
        .hero h1 { font-size: 1.8rem; font-weight: 700; }
        .hero p   { opacity: 0.88; font-size: 1rem; }
        .card-form {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .form-label { font-weight: 500; font-size: 0.9rem; color: #333; }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(46,139,87,0.15);
        }
        .btn-submit {
            background: var(--primary);
            border: none;
            padding: 12px 32px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: var(--accent); }
        .badge-status {
            background: var(--primary-light);
            color: var(--primary);
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 99px;
            font-weight: 600;
        }
        .info-box {
            background: var(--primary-light);
            border-left: 4px solid var(--accent);
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 0.88rem;
            color: #1a5f3f;
        }
        footer { font-size: 0.82rem; color: #888; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom px-3 px-md-5">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-megaphone-fill me-2" style="color:#2e8b57"></i>
        Puspeci <span>Cimuncang</span>
    </a>
    <a href="status.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-search me-1"></i> Cek Status
    </a>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="container text-center">
        <h1><i class="bi bi-shield-check me-2"></i>Pusat Pengaduan Masyarakat</h1>
        <p class="mb-0">Sampaikan keluhan dan aspirasi kamu untuk Cimuncang yang lebih baik.</p>
        <p class="mt-1"><small>Setiap pengaduan akan ditindaklanjuti oleh tim kami.</small></p>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">

            <!-- Pesan sukses -->
            <?php if ($pesan_sukses && $nomor_tiket): ?>
            <div class="alert alert-success d-flex align-items-start gap-3 rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-4 mt-1"></i>
                <div>
                    <strong>Pengaduan berhasil dikirim!</strong><br>
                    Nomor tiket kamu: <strong><?= htmlspecialchars($nomor_tiket) ?></strong><br>
                    <small class="text-muted">Catat atau screenshot nomor ini untuk mengecek status pengaduan.</small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pesan error -->
            <?php if ($pesan_error): ?>
            <div class="alert alert-danger rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($pesan_error) ?>
            </div>
            <?php endif; ?>

            <!-- Info box -->
            <div class="info-box mb-4">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Pengaduan bersifat anonim.</strong> Kamu tidak wajib mencantumkan nama atau nomor HP.
                Namun jika ingin dihubungi, isi kolom tersebut.
            </div>

            <!-- Form -->
            <div class="card card-form p-4 p-md-5">
                <h5 class="fw-bold mb-4">
                    <i class="bi bi-pencil-square me-2 text-success"></i>Form Pengaduan
                </h5>
                <form action="submit.php" method="POST" enctype="multipart/form-data" novalidate>

                    <!-- Nama -->
                    <div class="mb-3">
                        <label class="form-label">
                            Nama Pelapor
                            <span class="badge-status ms-1">Opsional</span>
                        </label>
                        <input type="text" name="nama_pelapor" class="form-control"
                               placeholder="Kosongkan jika ingin anonim" maxlength="100">
                    </div>

                    <!-- No HP -->
                    <div class="mb-3">
                        <label class="form-label">
                            Nomor HP / WhatsApp
                            <span class="badge-status ms-1">Opsional</span>
                        </label>
                        <input type="tel" name="no_hp" class="form-control"
                               placeholder="08xx-xxxx-xxxx" maxlength="20">
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">
                            Email
                            <span class="badge-status ms-1">Opsional</span>
                        </label>
                        <input type="email" name="email" class="form-control"
                               placeholder="contoh@email.com" maxlength="150">
                        <div class="form-text">Isi jika ingin mendapat konfirmasi via email.</div>
                    </div>

                    <!-- Kategori -->
                    <div class="mb-3">
                        <label class="form-label">
                            Kategori Pengaduan <span class="text-danger">*</span>
                        </label>
                        <select name="kategori" class="form-select" required>
                            <option value="" disabled selected>— Pilih kategori —</option>
                            <option>Infrastruktur</option>
                            <option>Keamanan</option>
                            <option>Kebersihan</option>
                            <option>Pelayanan Publik</option>
                            <option>Lainnya</option>
                        </select>
                    </div>

                    <!-- Judul -->
                    <div class="mb-3">
                        <label class="form-label">
                            Judul Pengaduan <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="judul" class="form-control" required
                               placeholder="Contoh: Jalan rusak di RT 03" maxlength="200">
                    </div>

                    <!-- Isi -->
                    <div class="mb-3">
                        <label class="form-label">
                            Isi Pengaduan <span class="text-danger">*</span>
                        </label>
                        <textarea name="isi_pengaduan" class="form-control" rows="5" required
                                  placeholder="Jelaskan detail pengaduan kamu di sini..."></textarea>
                    </div>

                    <!-- Foto -->
                    <div class="mb-4">
                        <label class="form-label">
                            Foto Pendukung
                            <span class="badge-status ms-1">Opsional</span>
                        </label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Format: JPG, PNG, WEBP. Maks. 2 MB.</div>
                    </div>

                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        $_SESSION['csrf']
                    ) ?>">

                    <div class="d-grid">
                        <button type="submit" class="btn btn-submit btn-success text-white">
                            <i class="bi bi-send-fill me-2"></i>Kirim Pengaduan
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<footer class="text-center py-4">
    Pusat Pengaduan Masyarakat Cimuncang &copy; <?= date('Y') ?> &middot; puspeci.sbs
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>