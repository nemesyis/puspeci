<?php
require_once 'config/db.php';

// Tolak request selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── 1. Ambil & bersihkan input ──────────────────────────────────────────────
$nama_pelapor  = trim($_POST['nama_pelapor'] ?? '');
$no_hp         = trim($_POST['no_hp']        ?? '');
$kategori      = trim($_POST['kategori']     ?? '');
$judul         = trim($_POST['judul']        ?? '');
$isi_pengaduan = trim($_POST['isi_pengaduan'] ?? '');

// Nama kosong → set anonim
if ($nama_pelapor === '') $nama_pelapor = 'Anonim';

// ── 2. Validasi field wajib ─────────────────────────────────────────────────
$kategori_valid = ['Infrastruktur','Keamanan','Kebersihan','Pelayanan Publik','Lainnya'];

if (!in_array($kategori, $kategori_valid)) {
    header('Location: index.php?error=Kategori+tidak+valid');
    exit;
}
if ($judul === '') {
    header('Location: index.php?error=Judul+pengaduan+wajib+diisi');
    exit;
}
if ($isi_pengaduan === '') {
    header('Location: index.php?error=Isi+pengaduan+wajib+diisi');
    exit;
}

// ── 3. Handle upload foto ───────────────────────────────────────────────────
$foto_path = null;

if (!empty($_FILES['foto']['name'])) {
    $file      = $_FILES['foto'];
    $allowed   = ['image/jpeg','image/png','image/webp'];
    $max_size  = 2 * 1024 * 1024; // 2 MB

    if (!in_array($file['type'], $allowed)) {
        header('Location: index.php?error=Format+foto+tidak+didukung+(JPG/PNG/WEBP+saja)');
        exit;
    }
    if ($file['size'] > $max_size) {
        header('Location: index.php?error=Ukuran+foto+melebihi+2+MB');
        exit;
    }

    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename  = uniqid('foto_', true) . '.' . strtolower($ext);
    $upload_to = __DIR__ . '/assets/uploads/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_to)) {
        header('Location: index.php?error=Gagal+mengunggah+foto,+coba+lagi');
        exit;
    }
    $foto_path = 'assets/uploads/' . $filename;
}

// ── 4. Generate nomor tiket unik ────────────────────────────────────────────
// Format: PPC-YYYYXXXX (PPC = PusPeCi, YYYY = tahun, XXXX = angka random)
function generateNomorTiket(mysqli $conn): string {
    do {
        $tiket = 'PPC-' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $cek   = $conn->prepare("SELECT id FROM pengaduan WHERE nomor_tiket = ?");
        $cek->bind_param('s', $tiket);
        $cek->execute();
        $cek->store_result();
        $exists = $cek->num_rows > 0;
        $cek->close();
    } while ($exists); // sangat jarang loop lebih dari sekali
    return $tiket;
}

$nomor_tiket = generateNomorTiket($conn);

// ── 5. Simpan ke database ───────────────────────────────────────────────────
$stmt = $conn->prepare("
    INSERT INTO pengaduan
        (nomor_tiket, nama_pelapor, no_hp, kategori, judul, isi_pengaduan, foto)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('sssssss',
    $nomor_tiket,
    $nama_pelapor,
    $no_hp,
    $kategori,
    $judul,
    $isi_pengaduan,
    $foto_path
);

if ($stmt->execute()) {
    // Sukses → redirect ke halaman utama dengan notifikasi
    header('Location: index.php?sukses=1&tiket=' . urlencode($nomor_tiket));
} else {
    header('Location: index.php?error=Gagal+menyimpan+pengaduan,+coba+lagi');
}

$stmt->close();
$conn->close();
exit;
