<?php
/**
 * admin/klaim.php
 * Master data seluruh klaim dari semua petugas (Read-only + Filter).
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Admin');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Data Master Klaim';
$activePage = 'klaim';

// ── Filter ──────────────────────────────────────────────────────
$f_jenis  = $_GET['jenis']     ?? '';
$f_status = $_GET['status']    ?? '';
$f_search = trim($_GET['cari'] ?? '');
$f_dari   = $_GET['dari']      ?? '';
$f_sampai = $_GET['sampai']    ?? '';

// ── Build Query ─────────────────────────────────────────────────
$where  = [];
$params = [];
$types  = '';

if ($f_jenis && in_array($f_jenis, ['JHT','JKK','JKM','JP'])) {
    $where[] = 'k.jenis_klaim = ?';
    $params[] = $f_jenis;
    $types   .= 's';
}
if ($f_status && in_array($f_status, ['Berkas Diterima','Verifikasi','Disetujui','Ditolak'])) {
    $where[] = 'k.status_klaim = ?';
    $params[] = $f_status;
    $types   .= 's';
}
if ($f_search) {
    $where[] = '(k.id_klaim LIKE ? OR p.nama_peserta LIKE ? OR p.no_kpj LIKE ?)';
    $like = '%' . $f_search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
if ($f_dari) {
    $where[] = 'k.tgl_pengajuan >= ?';
    $params[] = $f_dari;
    $types   .= 's';
}
if ($f_sampai) {
    $where[] = 'k.tgl_pengajuan <= ?';
    $params[] = $f_sampai;
    $types   .= 's';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT k.id_klaim, k.tgl_pengajuan, k.jenis_klaim, k.status_klaim, k.keterangan,
               p.no_kpj, p.nama_peserta, p.nama_perusahaan,
               u.nama_lengkap AS nama_petugas
        FROM klaim k
        JOIN peserta p ON k.no_kpj  = p.no_kpj
        JOIN users   u ON k.id_user = u.id_user
        {$whereClause}
        ORDER BY k.tgl_pengajuan DESC, k.id_klaim DESC";

$stmt = $koneksi->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// ── Hitung total setelah filter ─────────────────────────────────
$totalRows = $result->num_rows;

require_once __DIR__ . '/../includes/header.php';

function badgeStatus($s) {
    $map = ['Berkas Diterima'=>'badge-diterima','Verifikasi'=>'badge-verifikasi','Disetujui'=>'badge-disetujui','Ditolak'=>'badge-ditolak'];
    return '<span class="badge '.($map[$s]??'badge').'">'.htmlspecialchars($s).'</span>';
}
function badgeJenis($j) {
    return '<span class="badge badge-'.strtolower($j).'">'.htmlspecialchars($j).'</span>';
}
?>

<div class="page-header">
    <div>
        <h2>Data Master Klaim</h2>
        <p class="breadcrumb">Admin &rsaquo; <span>Data Klaim</span></p>
    </div>
    <a href="laporan.php" class="btn btn-print">&#9998; Cetak Laporan</a>
</div>

<!-- Filter Bar -->
<form method="GET" class="filter-bar">
    <div class="form-group">
        <label>Cari</label>
        <input type="text" name="cari" class="form-control" placeholder="ID Klaim / Nama / No KPJ"
            value="<?= htmlspecialchars($f_search) ?>">
    </div>
    <div class="form-group">
        <label>Jenis Klaim</label>
        <select name="jenis" class="form-control">
            <option value="">Semua Jenis</option>
            <?php foreach (['JHT','JKK','JKM','JP'] as $j): ?>
            <option value="<?= $j ?>" <?= $f_jenis===$j?'selected':'' ?>><?= $j ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <?php foreach (['Berkas Diterima','Verifikasi','Disetujui','Ditolak'] as $s): ?>
            <option value="<?= $s ?>" <?= $f_status===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Dari Tanggal</label>
        <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($f_dari) ?>">
    </div>
    <div class="form-group">
        <label>Sampai Tanggal</label>
        <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($f_sampai) ?>">
    </div>
    <div class="form-group" style="flex:0;">
        <label>&nbsp;</label>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="klaim.php" class="btn btn-outline-gray">Reset</a>
        </div>
    </div>
</form>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <h3>Daftar Klaim</h3>
        <span class="text-muted" style="font-size:.8rem;">Menampilkan <?= $totalRows ?> data</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Klaim</th>
                    <th>No KPJ</th>
                    <th>Peserta</th>
                    <th>Perusahaan</th>
                    <th>Petugas</th>
                    <th>Tgl Pengajuan</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if ($totalRows > 0):
                    while ($k = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code style="font-size:.75rem;"><?= htmlspecialchars($k['id_klaim']) ?></code></td>
                    <td><?= htmlspecialchars($k['no_kpj']) ?></td>
                    <td><?= htmlspecialchars($k['nama_peserta']) ?></td>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($k['nama_perusahaan']) ?>
                    </td>
                    <td><?= htmlspecialchars($k['nama_petugas']) ?></td>
                    <td><?= date('d/m/Y', strtotime($k['tgl_pengajuan'])) ?></td>
                    <td><?= badgeJenis($k['jenis_klaim']) ?></td>
                    <td><?= badgeStatus($k['status_klaim']) ?></td>
                    <td style="max-width:200px;font-size:.8rem;color:var(--gray-500);">
                        <?= $k['keterangan'] ? htmlspecialchars(mb_strimwidth($k['keterangan'], 0, 60, '…')) : '<em>—</em>' ?>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <div class="empty-icon">&#9654;</div>
                            <p>Tidak ada data klaim yang cocok dengan filter.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
