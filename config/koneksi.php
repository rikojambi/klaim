<?php
/**
 * koneksi.php
 * Konfigurasi dan koneksi database menggunakan MySQLi.
 * Semua halaman yang butuh database akan meng-include file ini.
 */

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');           // Sesuaikan password MySQL Anda
define('DB_NAME',    'db_bpjs_klaim');
define('DB_CHARSET', 'utf8mb4');

// Buat koneksi MySQLi
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($koneksi->connect_error) {
    // Tampilkan pesan error yang ramah (jangan expose detail di production)
    http_response_code(500);
    die('
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Koneksi Gagal</title>
        <style>
            body { font-family: sans-serif; display:flex; align-items:center;
                   justify-content:center; height:100vh; margin:0; background:#f0f0f0; }
            .box { background:#fff; padding:40px; border-radius:8px;
                   box-shadow:0 2px 15px rgba(0,0,0,.15); text-align:center; }
            h2   { color:#c0392b; }
            p    { color:#555; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>&#9888; Koneksi Database Gagal</h2>
            <p>Tidak dapat terhubung ke database MySQL.</p>
            <p><strong>Periksa:</strong> konfigurasi host, user, password, dan nama database di <code>config/koneksi.php</code>.</p>
        </div>
    </body>
    </html>
    ');
}

// Set charset agar karakter UTF-8 (termasuk emoji & aksara) tersimpan dengan benar
$koneksi->set_charset(DB_CHARSET);

/**
 * Helper: Escape string untuk keamanan (digunakan di query non-prepared)
 * Sebaiknya selalu gunakan Prepared Statements.
 */
function esc(mysqli $db, string $str): string {
    return $db->real_escape_string($str);
}
