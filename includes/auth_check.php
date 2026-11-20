<?php
/**
 * auth_check.php
 * Middleware / Guard Session.
 * Include file ini di SETIAP halaman yang membutuhkan proteksi login.
 *
 * Cara penggunaan:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   cekLogin();           // hanya cek login (semua role)
 *   cekRole('Admin');     // hanya untuk Admin
 *   cekRole('Petugas');   // hanya untuk Petugas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Pastikan user sudah login.
 * Jika belum, redirect ke halaman login.
 */
function cekLogin(): void {
    if (empty($_SESSION['id_user']) || empty($_SESSION['role'])) {
        header('Location: ' . getBaseUrl() . '/index.php?pesan=belum_login');
        exit;
    }
}

/**
 * Pastikan user sudah login DAN memiliki role yang benar.
 *
 * @param string $roleDibutuhkan 'Admin' atau 'Petugas'
 */
function cekRole(string $roleDibutuhkan): void {
    cekLogin();
    if ($_SESSION['role'] !== $roleDibutuhkan) {
        // Redirect ke dashboard sesuai role yang dimiliki
        $role = $_SESSION['role'];
        $url  = getBaseUrl() . '/' . strtolower($role) . '/dashboard.php?pesan=akses_ditolak';
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Dapatkan base URL project (tanpa trailing slash).
 * Berguna untuk redirect yang konsisten.
 */
function getBaseUrl(): string {
    $protokol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    // Cari posisi folder project dalam URL
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Naikkan ke root project (sistem-klaim-bpjs/)
    $parts    = explode('/', trim($scriptDir, '/'));
    // Temukan folder root project
    $rootIndex = array_search('sistem-klaim-bpjs', $parts);
    if ($rootIndex !== false) {
        $basePath = '/' . implode('/', array_slice($parts, 0, $rootIndex + 1));
    } else {
        // Fallback: pakai root
        $basePath = '/' . ($parts[0] ?? '');
    }
    return $protokol . '://' . $host . rtrim($basePath, '/');
}

/**
 * Ambil base URL relatif untuk path file (aset, link, dll.)
 */
function getBasePath(): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $parts     = explode('/', trim($scriptDir, '/'));
    $rootIndex = array_search('sistem-klaim-bpjs', $parts);
    if ($rootIndex !== false) {
        return '/' . implode('/', array_slice($parts, 0, $rootIndex + 1));
    }
    return '/' . ($parts[0] ?? '');
}
