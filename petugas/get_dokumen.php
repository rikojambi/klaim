<?php
/**
 * petugas/get_dokumen.php
 * AJAX endpoint — mengembalikan HTML daftar dokumen untuk sebuah klaim.
 * Dipanggil dari klaim_status.php via XMLHttpRequest.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekLogin();
require_once __DIR__ . '/../config/koneksi.php';

$id_klaim = trim($_GET['id_klaim'] ?? '');

if (!$id_klaim) {
    echo '<p class="text-muted">ID Klaim tidak valid.</p>';
    exit;
}

// Validasi: klaim ini harus milik user yang login (Petugas) atau admin boleh lihat semua
if ($_SESSION['role'] === 'Petugas') {
    $cek = $koneksi->prepare("SELECT id_klaim FROM klaim WHERE id_klaim=? AND id_user=?");
    $cek->bind_param('si', $id_klaim, $_SESSION['id_user']);
    $cek->execute();
    if ($cek->get_result()->num_rows === 0) {
        echo '<p class="text-muted">Akses ditolak.</p>';
        exit;
    }
    $cek->close();
}

$stmt = $koneksi->prepare(
    "SELECT id_dokumen, nama_dokumen, file_path, uploaded_at
     FROM dokumen_syarat WHERE id_klaim = ? ORDER BY id_dokumen ASC"
);
$stmt->bind_param('s', $id_klaim);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$base = getBasePath();

if ($result->num_rows === 0) {
    echo '<div class="empty-state"><div class="empty-icon">&#128196;</div><p>Tidak ada dokumen yang diupload untuk klaim ini.</p></div>';
    exit;
}

echo '<div class="doc-list">';
while ($dok = $result->fetch_assoc()) {
    $ext      = strtolower(pathinfo($dok['file_path'], PATHINFO_EXTENSION));
    $icon     = in_array($ext, ['jpg','jpeg','png']) ? '&#128247;' : '&#128196;';
    $fileUrl  = $base . '/' . htmlspecialchars($dok['file_path']);
    $tgl      = date('d/m/Y H:i', strtotime($dok['uploaded_at']));
    echo "
    <div class='doc-item'>
        <span class='doc-icon'>{$icon}</span>
        <div class='doc-name'>
            <strong>" . htmlspecialchars($dok['nama_dokumen']) . "</strong><br>
            <span class='text-muted'>" . strtoupper($ext) . " &mdash; Upload: {$tgl}</span>
        </div>
        <a href='{$fileUrl}' target='_blank' class='btn btn-outline btn-sm'>Buka</a>
    </div>";
}
echo '</div>';
?>
