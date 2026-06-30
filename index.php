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

// Kalau ada pesan sukses/error, langsung tampilkan form
$show_form = ($pesan_sukses || $pesan_error) ? 'true' : 'false';
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

        /* Navbar cek status — hidden by default */
        #navbar-cek-status {
            display: none;
        }

        /* Beating animation for navbar button */
        @keyframes heartbeat {
            0%   { transform: scale(1); }
            14%  { transform: scale(1.12); }
            28%  { transform: scale(1); }
            42%  { transform: scale(1.08); }
            56%  { transform: scale(1); }
            100% { transform: scale(1); }
        }
        .btn-beat {
            animation: heartbeat 1.4s ease-in-out infinite;
            transform-origin: center;
        }

        .hero {
            background: linear-gradient(135deg, #1a5f3f 0%, #2e8b57 100%);
            color: white;
            padding: 48px 0 36px;
        }
        .hero h1 { font-size: 1.8rem; font-weight: 700; }
        .hero p   { opacity: 0.88; font-size: 1rem; }

        /* Landing CTA buttons */
        .landing-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-top: 28px;
        }
        .btn-cta-primary {
            background: #fff;
            color: var(--primary);
            border: none;
            padding: 14px 40px;
            font-size: 1.05rem;
            font-weight: 700;
            border-radius: 10px;
            min-width: 230px;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
        }
        .btn-cta-primary:hover {
            background: #e8f5ee;
            box-shadow: 0 6px 20px rgba(0,0,0,0.17);
        }
        .btn-cta-secondary {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.6);
            padding: 12px 40px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            min-width: 230px;
            transition: border-color 0.18s, background 0.18s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-cta-secondary:hover {
            border-color: #fff;
            background: rgba(255,255,255,0.12);
            color: #fff;
        }

        /* Info box — hidden by default, slides in first */
        #info-box-wrapper {
            display: none;
            overflow: hidden;
        }
        #info-box-wrapper.slide-in {
            display: block;
            animation: slideDown 1.2s ease forwards;
        }

        /* Card form — hidden by default, slides in after delay */
        #card-form-wrapper {
            display: none;
            overflow: hidden;
        }
        #card-form-wrapper.slide-in {
            display: block;
            animation: slideDown 1.2s ease forwards;
        }

        /* Alert wrappers — same treatment */
        #alert-wrapper {
            display: none;
        }
        #alert-wrapper.slide-in {
            display: block;
            animation: slideDown 0.5s ease forwards;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUpOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-30px); }
        }

        @keyframes slideDownLanding {
        from { opacity: 0; transform: translateY(-30px); }
        to   { opacity: 1; transform: translateY(0); }
        }

        #landing-wrapper {
            opacity: 0;
        }
        #landing-wrapper.slide-in-landing {
            animation: slideDownLanding 0.6s ease forwards;
        }

        body.page-exit {
            animation: slideUpOut 0.4s ease forwards;
        }

        /* Outer container — always in DOM but invisible until triggered */
        #form-section {
            display: none;
        }
        #form-section.visible {
            display: block;
        }

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

        @media (prefers-reduced-motion: reduce) {
            .btn-beat { animation: none; }
            #info-box-wrapper.slide-in,
            #card-form-wrapper.slide-in,
            #alert-wrapper.slide-in { animation: none; }
        }

        /* ===== About Section (carousel + text box) ===== */
        .about-section {
            padding: 50px 0;
            background: #f4f6f5;
        }
        .about-carousel {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            min-height: 360px;
        }
        .about-carousel .carousel-item img {
            height: 360px;
            object-fit: contain;
            width: 100%;
            background: #1a1a1a;
        }
        .about-carousel .carousel-item {
            background: #d9d9d9; /* placeholder abu-abu */
            height: 360px;
        }
        .about-textbox {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(6px);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-top: -60px;
            margin-left: 24px;
            margin-right: 24px;
            position: relative;
            z-index: 2;
        }
        @media (min-width: 768px) {
            .about-textbox {
                margin-top: 0;
                margin-left: -60px;
                margin-right: 0;
            }
        }
        .about-textbox h5 {
            color: var(--primary);
            font-weight: 700;
        }
        .about-textbox .fun-fact-icon {
            width: 42px;
            height: 42px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 12px;
        }

        /* ===== Collaboration Section ===== */
        .collab-section {
            padding: 40px 0 60px;
            background: #fff;
            border-top: 1px solid #eee;
        }
        .collab-title {
            text-align: center;
            color: #888;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            margin-bottom: 28px;
        }
        .collab-logos {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 40px;
        }
        .collab-logo-box {
            width: 110px;
            height: 70px;
            background: #f4f6f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            padding: 8px;
            filter: grayscale(100%);
            opacity: 0.7;
            transition: filter 0.2s, opacity 0.2s;
        }
        .collab-logo-box:hover {
            filter: grayscale(0%);
            opacity: 1;
        }
        .collab-logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom px-3 px-md-5">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-megaphone-fill me-2" style="color:#2e8b57"></i>
        Puspeci <span>Cimuncang</span>
    </a>
    <a id="navbar-cek-status" href="status.php" class="btn btn-sm btn-outline-secondary" onclick="return gotoStatus(event)">
        <i class="bi bi-search me-1"></i> Cek Status
    </a>
</nav>

<!-- Hero -->
<div id="landing-wrapper">
    <div class="hero">
        <div class="container text-center">
            <h1><i class="bi bi-shield-check me-2"></i>Pusat Pengaduan Masyarakat</h1>
            <p class="mb-0">Sampaikan keluhan dan aspirasi kamu untuk Cimuncang yang lebih baik.</p>
            <p class="mt-1"><small>Setiap pengaduan akan ditindaklanjuti oleh tim kami.</small></p>

            <!-- CTA Buttons — hanya tampil kalau form belum dibuka -->
            <div class="landing-actions" id="landing-actions">
                <button class="btn-cta-primary" onclick="bukaPengaduan()">
                    <i class="bi bi-pencil-square me-2"></i>Buat Pengaduan
                </button>
                <a href="status.php" class="btn-cta-secondary" onclick="return gotoStatus(event)">
                    <i class="bi bi-search me-2"></i>Cek Status Pengaduanmu
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">

            <!-- Form section (hidden by default) -->
            <div id="form-section">

                <!-- Pesan sukses/error -->
                <div id="alert-wrapper">
                    <?php if ($pesan_sukses && $nomor_tiket): ?>
                    <div class="alert alert-success d-flex align-items-start gap-3 rounded-3 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill fs-4 mt-1"></i>
                        <div>
                            <strong>Pengaduan berhasil dikirim!</strong><br>
                            Nomor tiket kamu: <strong><?= htmlspecialchars($nomor_tiket) ?></strong><br>
                            <small class="text-muted">Catat atau screenshot nomor ini untuk melihat status pengaduan.</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($pesan_error): ?>
                    <div class="alert alert-danger rounded-3 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($pesan_error) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Info box — slides in first -->
                <div id="info-box-wrapper">
                    <div class="info-box mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Pengaduan bersifat anonim.</strong> Kamu tidak wajib mencantumkan nama atau nomor HP.
                        Namun jika ingin dihubungi, isi kolom tersebut.
                    </div>
                </div>

                <!-- Form card — slides in 1s after info box -->
                <div id="card-form-wrapper">
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
                                <div class="form-text">Isi jika ingin dihubungi.</div>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label class="form-label">
                                    Email
                                    <span class="badge-status ms-1">Opsional</span>
                                </label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="contoh@email.com" maxlength="150">
                                <div class="form-text">Isi jika ingin mendapat info proses pengaduan.</div>
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

                            <!-- RT/RW -->
                            <div class="mb-3">
                                <label class="form-label">
                                    RT / RW
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group">
                                            <span class="input-group-text">RT</span>
                                            <input type="text" name="rt" class="form-control" required
                                                placeholder="-" maxlength="5">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group">
                                            <span class="input-group-text">RW</span>
                                            <input type="text" name="rw" class="form-control" required
                                                placeholder="-" maxlength="5">
                                        </div>
                                    </div>
                                </div>
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

                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                            <div class="d-grid">
                                <button type="submit" class="btn btn-submit btn-success text-white">
                                    <i class="bi bi-send-fill me-2"></i>Kirim Pengaduan
                                </button>
                            </div>
                        </form>
                    </div>
                </div><!-- /#card-form-wrapper -->

            </div><!-- /#form-section -->

        </div>
    </div>
</div>

<!-- ===== About / Fun Fact Section ===== -->
<div class="about-section">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-7">
                <div id="aboutCarousel" class="carousel slide about-carousel" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <!-- Ganti src ini nanti dengan foto asli -->
                             <img src="assets/jarvistolongapakan.jpg" alt="Kegiatan KKM">
                        </div>
                        <div class="carousel-item">
                            <!-- Ganti src ini nanti dengan foto asli -->
                             <img src="assets/readingthescript.png" alt="Kegiatan KKM2">
                        </div>
                        <div class="carousel-item">
                            <!-- Ganti src ini nanti dengan foto asli -->
                             <img src="assets/aduhaimalas.jpg" alt="Kegiatan KKM3">
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#aboutCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#aboutCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            </div>
            <div class="col-12 col-md-5 d-flex align-items-center">
                <div class="about-textbox">
                    <div class="fun-fact-icon">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <h5 class="mb-2">Tahukah kamu?</h5>
                    <p class="mb-3" style="font-size:0.92rem; color:#444;">
                        Puspeci Cimuncang dikembangkan oleh mahasiswa Fakultas Teknologi Informasi
                        sebagai bagian dari riset penerapan teknologi untuk pelayanan masyarakat di tingkat RT/RW.
                        Platform ini dirancang agar warga bisa menyuarakan keluhan secara mudah, cepat, dan anonim.
                    </p>
                    <hr>
                    <p class="mb-0" style="font-size:0.85rem; color:#777;">
                        <i class="bi bi-people-fill me-1 text-success"></i>
                        Dibangun dengan semangat kolaborasi antara mahasiswa, warga, dan perangkat desa.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Collaboration Section ===== -->
<div class="collab-section">
    <div class="container">
        <div class="collab-title">In Collaboration With</div>
        <div class="collab-logos">
            <div class="collab-logo-box">Logo UNSERA</div>
            <div class="collab-logo-box">Logo Cimuncang</div>
            <div class="collab-logo-box">Logo Kecamatan</div>
        </div>
    </div>
</div>

<footer class="text-center py-4">
    Pusat Pengaduan Masyarakat Cimuncang &copy; <?= date('Y') ?> &middot; puspeci.sbs
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const formSection     = document.getElementById('form-section');
    const landingActions  = document.getElementById('landing-actions');
    const navbarCekStatus = document.getElementById('navbar-cek-status');
    const infoBoxWrapper  = document.getElementById('info-box-wrapper');
    const cardFormWrapper = document.getElementById('card-form-wrapper');
    const alertWrapper    = document.getElementById('alert-wrapper');

    function bukaPengaduan() {
        // Sembunyikan CTA di hero
        landingActions.style.display = 'none';

        // Tampilkan + animasi "Cek Status" di navbar
        navbarCekStatus.style.display = 'inline-flex';
        navbarCekStatus.classList.add('btn-beat');

        // Tampilkan container luar
        formSection.classList.add('visible');

        // Alert (sukses/error) langsung muncul kalau ada
        <?php if ($pesan_sukses || $pesan_error): ?>
        alertWrapper.classList.add('slide-in');
        <?php endif; ?>

        // Step 1: Info box slide in
        infoBoxWrapper.classList.add('slide-in');

        // Step 2: Form card slide in setelah 1 detik
        setTimeout(function () {
            cardFormWrapper.classList.add('slide-in');
        }, 1000);
    }

    function gotoStatus(e) {
        e.preventDefault();
        document.body.classList.add('page-exit');
        setTimeout(function () {
            window.location.href = 'status.php';
        }, 400); // samain sama durasi animasi CSS di atas
        return false;
    }

    window.addEventListener('DOMContentLoaded', function () {
        document.getElementById('landing-wrapper').classList.add('slide-in-landing');
    });

    // Kalau redirect balik dari submit.php dengan ?sukses= atau ?error=, langsung tampilkan semua
    <?php if ($show_form === 'true'): ?>
    bukaPengaduan();
    <?php endif; ?>
</script>
</body>
</html>