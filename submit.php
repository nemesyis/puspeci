<?php
require_once 'config/db.php';

// Start session for CSRF protection
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Tolak request selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF token verification
if (empty($_POST['csrf']) || empty($_SESSION['csrf']) ||
    !hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))
) {
    header('Location: index.php?error=Invalid+request');
    exit;
}

// ── 1. Ambil & bersihkan input ──────────────────────────────────────────────
$nama_pelapor  = trim($_POST['nama_pelapor'] ?? '');
$no_hp         = trim($_POST['no_hp']        ?? '');
$email         = trim($_POST['email']        ?? '');
$rt            = preg_replace('/\D/', '', $_POST['rt'] ?? '');
$rw            = preg_replace('/\D/', '', $_POST['rw'] ?? '');
$rt            = substr($rt, 0, 5);
$rw            = substr($rw, 0, 5);
$kategori      = trim($_POST['kategori']     ?? '');
$judul         = trim($_POST['judul']        ?? '');
$isi_pengaduan = trim($_POST['isi_pengaduan'] ?? '');

// Validasi format email kalau diisi
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=Format+email+tidak+valid');
    exit;
}

// Nama kosong → set anonim
if ($nama_pelapor === '') $nama_pelapor = 'Anonim';

// ── 2. Validasi field wajib ─────────────────────────────────────────────────
$kategori_valid = ['Infrastruktur','Keamanan','Kebersihan','Pelayanan Publik','Lainnya'];

if (!in_array($kategori, $kategori_valid)) {
    header('Location: index.php?error=Kategori+tidak+valid');
    exit;
}
if ($rt === '') {
    header('Location: index.php?error=RT+wajib+diisi');
    exit;
}
if ($rw === '') {
    header('Location: index.php?error=RW+wajib+diisi');
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

if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
    $file     = $_FILES['foto'];
    $max_size = 2 * 1024 * 1024; // 2 MB

    if ($file['size'] > $max_size) {
        header('Location: index.php?error=Ukuran+foto+melebihi+2+MB');
        exit;
    }

    // Detect MIME type from file content
    if (!function_exists('finfo_open')) {
        header('Location: index.php?error=Server+tidak+mendukung+validasi+file');
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($mime_map[$mime])) {
        header('Location: index.php?error=Format+foto+tidak+didukung+(JPG/PNG/WEBP+saja)');
        exit;
    }

    // Basic image validation
    if (!@getimagesize($file['tmp_name'])) {
        header('Location: index.php?error=File+bukan+gambar+valid');
        exit;
    }

    $ext = $mime_map[$mime];
    $upload_dir = __DIR__ . '/assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename = uniqid('foto_', true) . '.' . $ext;
    $final_path = $upload_dir . $filename;

    $saved = false;
    // Try to re-encode image (strip metadata) using GD
    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $img = @imagecreatefromjpeg($file['tmp_name']);
        if ($img !== false) {
            imagejpeg($img, $final_path, 90);
            imagedestroy($img);
            $saved = file_exists($final_path);
        }
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $img = @imagecreatefrompng($file['tmp_name']);
        if ($img !== false) {
            imagepng($img, $final_path);
            imagedestroy($img);
            $saved = file_exists($final_path);
        }
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($file['tmp_name']);
        if ($img !== false) {
            imagewebp($img, $final_path, 80);
            imagedestroy($img);
            $saved = file_exists($final_path);
        }
    }

    // Fallback: move uploaded file (only if re-encode not available)
    if (!$saved) {
        if (!move_uploaded_file($file['tmp_name'], $final_path)) {
            header('Location: index.php?error=Gagal+mengunggah+foto,+coba+lagi');
            exit;
        }
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
        (nomor_tiket, nama_pelapor, no_hp, email, kategori, rt, rw, judul, isi_pengaduan, foto)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssssssss',
    $nomor_tiket,
    $nama_pelapor,
    $no_hp,
    $email,
    $kategori,
    $rt,
    $rw,
    $judul,
    $isi_pengaduan,
    $foto_path
);

if ($stmt->execute()) {

    // ── Kirim email notifikasi ke pelapor (kalau email diisi) ────────────────
    if ($email !== '') {
        require_once __DIR__ . '/config/mail.php';

        $subject  = "[Puspeci] Pengaduan diterima — $nomor_tiket";
        $body     = "Halo $nama_pelapor,\n\n";
        $body    .= "Pengaduan kamu telah berhasil kami terima. Berikut detailnya:\n\n";
        $body    .= "Nomor Tiket : $nomor_tiket\n";
        $body    .= "Kategori    : $kategori\n";
        $body    .= "RT / RW     : " . ($rt && $rw ? "RT $rt / RW $rw" : "-") . "\n";
        $body    .= "Judul       : $judul\n";
        $body    .= "Status      : Masuk\n\n";
        $body    .= "Pantau status pengaduan di:\n";
        $body    .= "https://puspeci.sbs/status.php?tiket=" . urlencode($nomor_tiket) . "\n\n";
        $body    .= "Simpan nomor tiket untuk keperluan pengecekan status.\n\n";
        $body    .= "Terima kasih,\nPusat Pengaduan Masyarakat Cimuncang\npuspeci.sbs";

        kirim_email($email, $subject, $body);
        // gagal kirim tidak menghentikan proses — pengaduan tetap tersimpan
    }

    header('Location: index.php?sukses=1&tiket=' . urlencode($nomor_tiket));
} else {
    header('Location: index.php?error=Gagal+menyimpan+pengaduan,+coba+lagi');
}

$stmt->close();
$conn->close();
exit;