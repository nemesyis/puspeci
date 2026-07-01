<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data pengaduan
$stmt = $conn->prepare("SELECT * FROM pengaduan WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) {
    header('Location: dashboard.php');
    exit;
}

$pesan_sukses = '';
$pesan_error  = '';

// ── Update status ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status_valid = ['Masuk','Diproses','Selesai','Ditolak'];
    $new_status   = $_POST['status'];

    if (in_array($new_status, $status_valid)) {
        $upd = $conn->prepare("UPDATE pengaduan SET status = ? WHERE id = ?");
        $upd->bind_param('si', $new_status, $id);
        if ($upd->execute()) {
            $p['status']  = $new_status;
            $pesan_sukses = "Status berhasil diperbarui menjadi <strong>$new_status</strong>.";

            // ── Kirim email notifikasi ke pelapor jika ada email ────────────
            if (!empty($p['email'])) {
                require_once __DIR__ . '/../config/mail.php';

                $subject  = "[Puspeci] Update status pengaduan {$p['nomor_tiket']}";
                $body     = "Halo {$p['nama_pelapor']},\n\n";
                $body    .= "Status pengaduan kamu telah diperbarui:\n\n";
                $body    .= "Nomor Tiket : {$p['nomor_tiket']}\n";
                $body    .= "Kategori    : {$p['kategori']}\n";
                $body    .= "RT / RW     : " . ($p['rt'] && $p['rw'] ? "RT {$p['rt']} / RW {$p['rw']}" : "-") . "\n";
                $body    .= "Judul       : {$p['judul']}\n";
                $body    .= "Status baru : $new_status\n\n";
                $body    .= "Pantau detail pengaduan di:\n";
                $body    .= "https://puspeci.sbs/status.php?tiket=" . urlencode($p['nomor_tiket']) . "\n\n";
                $body    .= "Terima kasih,\nPusat Pengaduan Masyarakat Cimuncang\npuspeci.sbs";

                kirim_email($p['email'], $subject, $body);
            }

        } else {
            $pesan_error = 'Gagal memperbarui status.';
        }
        $upd->close();
    }
}

$badge_map = [
    'Masuk'    => 'secondary',
    'Diproses' => 'warning text-dark',
    'Selesai'  => 'success',
    'Ditolak'  => 'danger',
];
$badge = $badge_map[$p['status']] ?? 'secondary';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengaduan — Puspeci Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            background: #1a5f3f; min-height: 100vh;
            width: 220px; position: fixed; top: 0; left: 0;
            padding: 24px 0; z-index: 100;
        }
        .sidebar .brand {
            color: white; font-weight: 700; font-size: 1rem;
            padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.15);
            display: flex; align-items: center; gap: 8px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.75); padding: 10px 20px;
            display: flex; align-items: center; gap: 10px;
            font-size: .9rem; transition: all 0.15s;
        }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.12); }
        .sidebar .nav-label {
            color: rgba(255,255,255,0.4); font-size: .7rem;
            text-transform: uppercase; letter-spacing: .08em;
            padding: 16px 20px 4px;
        }
        .main { margin-left: 220px; padding: 28px; }
        .detail-card {
            background: white; border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 28px;
        }
        .field-label {
            font-size: .75rem; text-transform: uppercase;
            letter-spacing: .06em; color: #888; font-weight: 600; margin-bottom: 4px;
        }
        .field-value { font-size: .92rem; color: #222; }
        .field-row { padding: 14px 0; border-bottom: 1px solid #f0f0f0; }
        .field-row:last-child { border-bottom: none; }
        .form-select:focus {
            border-color: #2e8b57; box-shadow: 0 0 0 3px rgba(46,139,87,0.12);
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand"><i class="bi bi-megaphone-fill"></i> Puspeci Admin</div>
    <div class="nav-label">Menu</div>
    <a href="dashboard.php" class="nav-link">
        <i class="bi bi-grid-1x2"></i> Dashboard
    </a>
    <a href="../index.php" target="_blank" class="nav-link">
        <i class="bi bi-box-arrow-up-right"></i> Halaman Publik
    </a>
    <div class="nav-label">Akun</div>
    <a href="dashboard.php?logout=1" class="nav-link"
       onclick="return confirm('Yakin mau logout?')">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<!-- Main -->
<div class="main">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb" style="font-size:.85rem">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-success">Dashboard</a></li>
            <li class="breadcrumb-item active">Detail Pengaduan</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">Detail Pengaduan</h5>
            <code style="font-size:.85rem;color:#2e8b57"><?= htmlspecialchars($p['nomor_tiket']) ?></code>
        </div>
        <span class="badge bg-<?= $badge ?> fs-6 px-3 py-2">
            <?= htmlspecialchars($p['status']) ?>
        </span>
    </div>

    <?php if ($pesan_sukses): ?>
    <div class="alert alert-success py-2 mb-3" style="font-size:.88rem">
        <i class="bi bi-check-circle me-1"></i><?= $pesan_sukses ?>
    </div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
    <div class="alert alert-danger py-2 mb-3" style="font-size:.88rem">
        <i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($pesan_error) ?>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Detail kiri -->
        <div class="col-12 col-lg-8">
            <div class="detail-card">
                <div class="field-row">
                    <div class="field-label">Judul Pengaduan</div>
                    <div class="field-value fw-600"><?= htmlspecialchars($p['judul']) ?></div>
                </div>
                <div class="field-row">
                    <div class="field-label">Kategori</div>
                    <div class="field-value">
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <?= htmlspecialchars($p['kategori']) ?>
                        </span>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">RT / RW</div>
                    <div class="field-value">
                        <?php if ($p['rt'] && $p['rw']): ?>
                            RT <?= htmlspecialchars($p['rt']) ?> / RW <?= htmlspecialchars($p['rw']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Isi Pengaduan</div>
                    <div class="field-value" style="white-space:pre-wrap;line-height:1.7">
                        <?= htmlspecialchars($p['isi_pengaduan']) ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Kritik dan Saran</div>
                    <div class="field-value" style="white-space:pre-wrap;line-height:1.7">
                        <?php if (!empty($p['kritik_saran'])): ?>
                            <?= htmlspecialchars($p['kritik_saran']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($p['foto']): ?>
                <div class="field-row">
                    <div class="field-label">Foto Lampiran</div>
                    <a href="../<?= htmlspecialchars($p['foto']) ?>" target="_blank">
                        <img src="../<?= htmlspecialchars($p['foto']) ?>"
                             class="img-fluid rounded mt-1" style="max-height:260px"
                             alt="Foto pengaduan">
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel kanan -->
        <div class="col-12 col-lg-4">
            <!-- Info pelapor -->
            <div class="detail-card mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-success"></i>Info Pelapor</h6>
                <div class="field-row">
                    <div class="field-label">Nama</div>
                    <div class="field-value"><?= htmlspecialchars($p['nama_pelapor']) ?></div>
                </div>
                <div class="field-row">
                    <div class="field-label">No. HP</div>
                    <div class="field-value">
                        <?php if ($p['no_hp']): ?>
                        <a href="https://wa.me/62<?= ltrim($p['no_hp'], '0') ?>" target="_blank"
                           class="text-success text-decoration-none">
                            <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($p['no_hp']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Email</div>
                    <div class="field-value">
                        <?php if ($p['email']): ?>
                        <a href="mailto:<?= htmlspecialchars($p['email']) ?>"
                           class="text-success text-decoration-none">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($p['email']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Dikirim</div>
                    <div class="field-value"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?> WIB</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Diperbarui</div>
                    <div class="field-value"><?= date('d M Y, H:i', strtotime($p['updated_at'])) ?> WIB</div>
                </div>
            </div>

            <!-- Update status -->
            <div class="detail-card">
                <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-success"></i>Perbarui Status</h6>
                <form method="POST">
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <?php foreach (['Masuk','Diproses','Selesai','Ditolak'] as $s): ?>
                            <option <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-sm fw-600">
                            <i class="bi bi-check-lg me-1"></i>Simpan Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>