<?php
require_once 'config/db.php';

$pengaduan   = null;
$not_found   = false;
$nomor_input = '';

if (isset($_GET['tiket']) && $_GET['tiket'] !== '') {
    $nomor_input = strtoupper(trim($_GET['tiket']));
    $stmt = $conn->prepare("SELECT * FROM pengaduan WHERE nomor_tiket = ?");
    $stmt->bind_param('s', $nomor_input);
    $stmt->execute();
    $result    = $stmt->get_result();
    $pengaduan = $result->fetch_assoc();
    if (!$pengaduan) $not_found = true;
    $stmt->close();
}

// Badge warna per status
$status_badge = [
    'Masuk'    => 'secondary',
    'Diproses' => 'warning text-dark',
    'Selesai'  => 'success',
    'Ditolak'  => 'danger',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pengaduan — Puspeci Cimuncang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f5; font-family: 'Segoe UI', sans-serif; }
        .hero {
            background: linear-gradient(135deg, #1a5f3f 0%, #2e8b57 100%);
            color: white; padding: 36px 0 28px;
        }
        .card-result { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .form-control:focus {
            border-color: #2e8b57;
            box-shadow: 0 0 0 3px rgba(46,139,87,0.15);
        }
        .detail-row { border-bottom: 1px solid #f0f0f0; padding: 10px 0; font-size: 0.9rem; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        footer { font-size: 0.82rem; color: #888; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        #page-wrapper {
            opacity: 0;
        }
        #page-wrapper.slide-in {
            animation: slideDown 0.6s ease forwards;
        }

        @keyframes slideUpOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-30px); }
        }

        body.page-exit {
            animation: slideUpOut 0.4s ease forwards;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom px-3 px-md-5">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-megaphone-fill me-2" style="color:#2e8b57"></i>
        Puspeci <span style="color:#2e8b57">Cimuncang</span>
    </a>
    <a href="index.php" class="btn btn-sm btn-outline-success" onclick="return gotoIndex(event)">
        <i class="bi bi-plus-lg me-1"></i> Buat Pengaduan
    </a>
</nav>

<div id="page-wrapper">
<div class="hero">
    <div class="container py-4">
        <h1 class="fs-3 fw-bold"><i class="bi bi-search me-2"></i>Cek Status Pengaduan</h1>
        <p class="mb-0 opacity-75">Masukkan nomor tiket yang kamu terima saat mengirim pengaduan.</p>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-7 col-lg-6">

            <!-- Form cari -->
            <div class="card card-result p-4 mb-4">
                <form method="GET" action="status.php">
                    <label class="form-label fw-500">Nomor Tiket</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-ticket-perforated"></i></span>
                        <input type="text" name="tiket" class="form-control text-uppercase"
                               placeholder="Contoh: PPC-20250001"
                               value="<?= htmlspecialchars($nomor_input) ?>"
                               style="letter-spacing:0.05em" required>
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Not found -->
            <?php if ($not_found): ?>
            <div class="alert alert-warning rounded-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Nomor tiket <strong><?= htmlspecialchars($nomor_input) ?></strong> tidak ditemukan.
                Pastikan nomor sudah benar.
            </div>
            <?php endif; ?>

            <!-- Hasil -->
            <?php if ($pengaduan): ?>
            <?php $badge = $status_badge[$pengaduan['status']] ?? 'secondary'; ?>
            <div class="card card-result p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-ticket-perforated-fill me-2 text-success"></i>
                        <?= htmlspecialchars($pengaduan['nomor_tiket']) ?>
                    </h6>
                    <span class="badge bg-<?= $badge ?> fs-6 px-3 py-2">
                        <?= htmlspecialchars($pengaduan['status']) ?>
                    </span>
                </div>
                <hr>
                <div class="detail-row">
                    <div class="detail-label">Judul</div>
                    <div class="fw-500"><?= htmlspecialchars($pengaduan['judul']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Kategori</div>
                    <div><?= htmlspecialchars($pengaduan['kategori']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Isi Pengaduan</div>
                    <div><?= nl2br(htmlspecialchars($pengaduan['isi_pengaduan'])) ?></div>
                </div>
                <?php if (!empty($pengaduan['kritik_saran'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Kritik dan Saran</div>
                    <div><?= nl2br(htmlspecialchars($pengaduan['kritik_saran'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($pengaduan['foto']): ?>
                <div class="detail-row">
                    <div class="detail-label">Foto</div>
                    <img src="<?= htmlspecialchars($pengaduan['foto']) ?>"
                         class="img-fluid rounded mt-1" style="max-height:200px" alt="Foto pengaduan">
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <div class="detail-label">Dikirim pada</div>
                    <div><?= date('d M Y, H:i', strtotime($pengaduan['created_at'])) ?> WIB</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Terakhir diperbarui</div>
                    <div><?= date('d M Y, H:i', strtotime($pengaduan['updated_at'])) ?> WIB</div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<footer class="text-center py-4">
    Pusat Pengaduan Masyarakat Cimuncang &copy; <?= date('Y') ?> &middot; puspeci.sbs
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', function () {
        document.getElementById('page-wrapper').classList.add('slide-in');
    });

        function gotoIndex(e) {
        e.preventDefault();
        document.body.classList.add('page-exit');
        setTimeout(function () {
            window.location.href = 'index.php';
        }, 400);
        return false;
    }
</script>
</body>
</html>