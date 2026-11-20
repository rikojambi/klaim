<?php
/**
 * index.php — Halaman Login
 * Titik masuk utama aplikasi. Menangani proses autentikasi.
 */

session_start();
require_once __DIR__ . '/config/koneksi.php';

// Jika sudah login, langsung redirect ke dashboard sesuai role
if (!empty($_SESSION['id_user'])) {
    $role = strtolower($_SESSION['role']);
    header("Location: {$role}/dashboard.php");
    exit;
}

$error   = '';
$success = '';

// Pesan dari redirect
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] === 'logout')       $success = 'Anda berhasil keluar dari sistem.';
    if ($_GET['pesan'] === 'belum_login')  $error   = 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.';
    if ($_GET['pesan'] === 'akses_ditolak')$error   = 'Akses ditolak. Anda tidak memiliki izin untuk halaman tersebut.';
}

// Proses login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Query dengan Prepared Statement
        $stmt = $koneksi->prepare(
            "SELECT id_user, username, password, nama_lengkap, role
             FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID untuk mencegah session fixation
            session_regenerate_id(true);

            $_SESSION['id_user']      = $user['id_user'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];

            $tujuan = strtolower($user['role']);
            header("Location: {$tujuan}/dashboard.php");
            exit;
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Klaim BPJS Ketenagakerjaan</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-card">
        <!-- Logo & Header -->
        <div class="login-header">
            <div class="login-logo">
                <span class="logo-square">&#9632;</span>
                <span class="logo-square logo-sq2">&#9632;</span>
            </div>
            <h2 class="login-title">BPJS Ketenagakerjaan</h2>
            <p class="login-subtitle">Sistem Perekaman Klaim</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Form Login -->
        <form action="index.php" method="POST" class="login-form" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">&#9786;</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrap">
                    <span class="input-icon">&#128274;</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Masuk ke Sistem
            </button>
        </form>

        <!-- Info akun demo -->
        <div class="login-info-box">
            <p><strong>Akun Demo:</strong></p>
            <p>Admin &nbsp;&nbsp;: <code>admin</code> / <code>admin123</code></p>
            <p>Petugas: <code>petugas1</code> / <code>petugas123</code></p>
        </div>
    </div>

    <p class="login-footer-text">
        &copy; <?= date('Y') ?> Sistem Klaim BPJS Ketenagakerjaan &mdash; Kelas 12 RPL
    </p>
</div>

</body>
</html>
