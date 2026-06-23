<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

// ── Logout ────────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── Filter & search ───────────────────────────────────────────────────────────
$filter_status   = $_GET['status']   ?? '';
$filter_kategori = $_GET['kategori'] ?? '';
$search          = trim($_GET['q']   ?? '');

$where  = [];
$params = [];
$types  = '';

if ($filter_status !== '') {
    $where[] = 'status = ?';
    $params[] = $filter_status;
    $types   .= 's';
}
if ($filter_kategori !== '') {
    $where[] = 'kategori = ?';
    $params[] = $filter_kategori;
    $types   .= 's';
}
if ($search !== '') {
    $where[] = '(nomor_tiket LIKE ? OR judul LIKE ? OR nama_pelapor LIKE ?)';
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}

$sql = "SELECT * FROM pengaduan";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result      = $stmt->get_result();
$pengaduans  = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Statistik ringkas ─────────────────────────────────────────────────────────
$stats = [];
foreach (['Masuk','Diproses','Selesai','Ditolak'] as $s) {
    $r = $conn->query("SELECT COUNT(*) as n FROM pengaduan WHERE status = '$s'");
    $stats[$s] = $r->fetch_assoc()['n'];
}
$total = array_sum($stats);

// Badge warna
$badge_map = [
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
    <title>Dashboard Admin — Puspeci Cimuncang</title>
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
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white; background: rgba(255,255,255,0.12);
        }
        .sidebar .nav-label {
            color: rgba(255,255,255,0.4); font-size: .7rem;
            text-transform: uppercase; letter-spacing: .08em;
            padding: 16px 20px 4px;
        }
        .main { margin-left: 220px; padding: 28px; }
        .stat-card {
            background: white; border-radius: 14px;
            border: none; padding: 20px 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .stat-num  { font-size: 2rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: .82rem; color: #888; margin-top: 4px; }
        .table-card {
            background: white; border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden;
        }
        .table thead th {
            background: #f8faf9; font-size: .8rem;
            text-transform: uppercase; letter-spacing: .05em;
            color: #666; border-bottom: 1px solid #eee; font-weight: 600;
        }
        .table tbody tr:hover { background: #f8faf9; }
        .table td { font-size: .88rem; vertical-align: middle; }
        .form-control:focus, .form-select:focus {
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
    <div class="brand">
        <i class="bi bi-megaphone-fill"></i> Puspeci Admin
    </div>
    <div class="nav-label">Menu</div>
    <a href="dashboard.php" class="nav-link active">
        <i class="bi bi-grid-1x2"></i> Dashboard
    </a>
    <a href="../index.php" target="_blank" class="nav-link">
        <i class="bi bi-box-arrow-up-right"></i> Halaman Publik
    </a>
    <div class="nav-label">Akun</div>
    <a href="?logout=1" class="nav-link"
       onclick="return confirm('Yakin mau logout?')">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<!-- Main -->
<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Dashboard Pengaduan</h5>
            <small class="text-muted">Halo, <?= htmlspecialchars($_SESSION['admin_user']) ?></small>
        </div>
        <span class="text-muted" style="font-size:.82rem"><?= date('d F Y') ?></span>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num text-dark"><?= $total ?></div>
                <div class="stat-label"><i class="bi bi-inbox me-1"></i>Total</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num text-secondary"><?= $stats['Masuk'] ?></div>
                <div class="stat-label"><i class="bi bi-envelope me-1"></i>Masuk</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num text-warning"><?= $stats['Diproses'] ?></div>
                <div class="stat-label"><i class="bi bi-arrow-repeat me-1"></i>Diproses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num text-success"><?= $stats['Selesai'] ?></div>
                <div class="stat-label"><i class="bi bi-check-circle me-1"></i>Selesai</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="table-card mb-0">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Cari nomor tiket / judul / nama..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <?php foreach (['Masuk','Diproses','Selesai','Ditolak'] as $s): ?>
                        <option <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="kategori" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        <?php foreach (['Infrastruktur','Keamanan','Kebersihan','Pelayanan Publik','Lainnya'] as $k): ?>
                        <option <?= $filter_kategori === $k ? 'selected' : '' ?>><?= $k ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No. Tiket</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Pelapor</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($pengaduans)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada pengaduan ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pengaduans as $p): ?>
                    <tr>
                        <td><code style="font-size:.8rem"><?= htmlspecialchars($p['nomor_tiket']) ?></code></td>
                        <td><?= htmlspecialchars(mb_strimwidth($p['judul'], 0, 40, '...')) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['kategori']) ?></span></td>
                        <td><?= htmlspecialchars($p['nama_pelapor']) ?></td>
                        <td>
                            <span class="badge bg-<?= $badge_map[$p['status']] ?>">
                                <?= htmlspecialchars($p['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td>
                            <a href="detail.php?id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-success">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-3 py-2 text-muted border-top" style="font-size:.8rem">
            Menampilkan <?= count($pengaduans) ?> dari <?= $total ?> total pengaduan
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
