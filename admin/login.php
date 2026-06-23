<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Kalau sudah login, langsung ke dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// ── Kredensial admin ─────────────────────────────────────────────────────────
// GANTI password ini sebelum deploy ke production!
// Generate hash baru: php -r "echo password_hash('passwordmu', PASSWORD_DEFAULT);"
define('ADMIN_USER', 'admin');
define('ADMIN_HASH', password_hash('admin123', PASSWORD_DEFAULT)); // ← ganti setelah testing

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USER && password_verify($password, ADMIN_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $username;
        session_regenerate_id(true); // cegah session fixation
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Puspeci Cimuncang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a5f3f 0%, #2e8b57 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.18);
            width: 100%; max-width: 400px;
        }
        .login-icon {
            width: 64px; height: 64px;
            background: #e8f5ee;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px; color: #1a5f3f;
        }
        .form-control:focus {
            border-color: #2e8b57;
            box-shadow: 0 0 0 3px rgba(46,139,87,0.15);
        }
        .btn-login {
            background: #1a5f3f; border: none;
            font-weight: 600; padding: 11px;
            border-radius: 8px; transition: background 0.2s;
        }
        .btn-login:hover { background: #2e8b57; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-icon"><i class="bi bi-shield-lock-fill"></i></div>
    <h5 class="text-center fw-bold mb-1">Admin Panel</h5>
    <p class="text-center text-muted mb-4" style="font-size:.88rem">Pusat Pengaduan Masyarakat Cimuncang</p>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3" style="font-size:.88rem">
        <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['redirect'])): ?>
    <div class="alert alert-warning py-2 px-3" style="font-size:.88rem">
        <i class="bi bi-lock me-1"></i>Silakan login terlebih dahulu.
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-500">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="admin" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-500">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-login btn-success text-white">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </div>
    </form>

    <div class="text-center mt-4">
        <a href="../index.php" class="text-muted text-decoration-none" style="font-size:.82rem">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman publik
        </a>
    </div>
</div>
</body>
</html>
