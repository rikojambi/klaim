<?php
/**
 * admin/laporan.php
 * Cetak laporan klaim dengan filter rentang tanggal & jenis klaim.
 * Menggunakan @media print CSS agar tampilan cetak bersih.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Admin');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Cetak Laporan Klaim';
$activePage = 'laporan';

// ── Filter ─────────────────────────────────────────────────────
$f_jenis  = $_GET['jenis']  ?? '';
$f_status = $_GET['status'] ?? '';
$f_dari   = $_GET['dari']   ?? date('Y-m-01');   // default: awal bulan ini
$f_sampai = $_GET['sampai'] ?? date('Y-m-d');     // default: hari ini

// ── Build Query ─────────────────────────────────────────────────
$where  = ["k.tgl_pengajuan BETWEEN ? AND ?"];
$params = [$f_dari, $f_sampai];
$types  = 'ss';

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

$whereClause = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT k.id_klaim, k.tgl_pengajuan, k.jenis_klaim, k.status_klaim, k.keterangan,
               p.no_kpj, p.nama_peserta, p.nama_perusahaan,
               u.nama_lengkap AS nama_petugas
        FROM klaim k
        JOIN peserta p ON k.no_kpj  = p.no_kpj
        JOIN users   u ON k.id_user = u.id_user
        {$whereClause}
        ORDER BY k.tgl_pengajuan ASC, k.id_klaim ASC";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$rows = $result->fetch_all(MYSQLI_ASSOC);

// ── Statistik Cetak ─────────────────────────────────────────────
$totalCetak = count($rows);
$perStatus  = [];
$perJenis   = [];
foreach ($rows as $r) {
    $perStatus[$r['status_klaim']] = ($perStatus[$r['status_klaim']] ?? 0) + 1;
    $perJenis[$r['jenis_klaim']]   = ($perJenis[$r['jenis_klaim']]   ?? 0) + 1;
}

require_once __DIR__ . '/../includes/header.php';

function bs($s) {
    $map=['Berkas Diterima'=>'badge-diterima','Verifikasi'=>'badge-verifikasi','Disetujui'=>'badge-disetujui','Ditolak'=>'badge-ditolak'];
    return '<span class="badge '.($map[$s]??'badge').'">'.htmlspecialchars($s).'</span>';
}
function bj($j) { return '<span class="badge badge-'.strtolower($j).'">'.htmlspecialchars($j).'</span>'; }

$tglDariTampil   = date('d F Y', strtotime($f_dari));
$tglSampaiTampil = date('d F Y', strtotime($f_sampai));
?>

<div class="page-header no-print">
    <div>
        <h2>Cetak Laporan Klaim</h2>
        <p class="breadcrumb">Admin &rsaquo; <span>Laporan</span></p>
    </div>
    <div style="display:flex;gap:10px;">
        <button onclick="window.print()" class="btn btn-print">&#128438; Cetak / Simpan PDF</button>
    </div>
</div>

<!-- Filter Form (no-print) -->
<form method="GET" class="filter-bar no-print">
    <div class="form-group">
        <label>Dari Tanggal</label>
        <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($f_dari) ?>" required>
    </div>
    <div class="form-group">
        <label>Sampai Tanggal</label>
        <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($f_sampai) ?>" required>
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
    <div class="form-group" style="flex:0;">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<!-- ============================================================
     AREA YANG AKAN TERCETAK
     ============================================================ -->

<!-- Header Cetak (hanya terlihat saat print) -->
<div class="print-header">
    <h1>BPJS KETENAGAKERJAAN</h1>
    <h2 style="font-size:14px;margin:4px 0;">LAPORAN REKAP KLAIM</h2>
    <p>Periode: <?= $tglDariTampil ?> s.d. <?= $tglSampaiTampil ?></p>
    <p>
        Jenis: <?= $f_jenis ?: 'Semua Jenis' ?> |
        Status: <?= $f_status ?: 'Semua Status' ?> |
        Dicetak: <?= date('d F Y H:i') ?> oleh <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
    </p>
</div>

<!-- Ringkasan Statistik -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>Ringkasan Laporan &mdash; Periode: <?= $tglDariTampil ?> s.d. <?= $tglSampaiTampil ?></h3>
        <span class="badge badge-disetujui">Total: <?= $totalCetak ?> Klaim</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;">
            <?php
            $statusList = ['Berkas Diterima','Verifikasi','Disetujui','Ditolak'];
            $jenisColors= ['JHT'=>'green','JKK'=>'blue','JKM'=>'orange','JP'=>'red'];
            foreach ($statusList as $s):
                $jml = $perStatus[$s] ?? 0;
            ?>
            <div style="text-align:center;padding:14px;background:var(--gray-50);border-radius:var(--border-radius);border:1px solid var(--gray-200);">
                <?= bs($s) ?>
                <div style="font-size:1.5rem;font-weight:800;color:var(--blue-dark);margin-top:8px;"><?= $jml ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tabel Detail -->
<div class="card">
    <div class="card-header">
        <h3>Detail Klaim</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Klaim</th>
                    <th>No KPJ</th>
                    <th>Nama Peserta</th>
                    <th>Perusahaan</th>
                    <th>Petugas</th>
                    <th>Tgl Pengajuan</th>
                    <th>Jenis</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totalCetak > 0): $no = 1; foreach ($rows as $k): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code style="font-size:.75rem;"><?= htmlspecialchars($k['id_klaim']) ?></code></td>
                    <td><?= htmlspecialchars($k['no_kpj']) ?></td>
                    <td><?= htmlspecialchars($k['nama_peserta']) ?></td>
                    <td><?= htmlspecialchars($k['nama_perusahaan']) ?></td>
                    <td><?= htmlspecialchars($k['nama_petugas']) ?></td>
                    <td><?= date('d/m/Y', strtotime($k['tgl_pengajuan'])) ?></td>
                    <td><?= bj($k['jenis_klaim']) ?></td>
                    <td><?= bs($k['status_klaim']) ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <div class="empty-icon">&#9654;</div>
                            <p>Tidak ada data klaim pada periode dan filter yang dipilih.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Footer Cetak -->
<div class="print-footer">
    <p>Laporan ini digenerate secara otomatis oleh Sistem Klaim BPJS Ketenagakerjaan.</p>
    <p>Dicetak pada: <?= date('d F Y, H:i:s') ?> WIB &nbsp;|&nbsp; Dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>

    <div class="print-signature">
        <div class="signature-box">
            <div>Diketahui oleh,</div>
            <div class="signature-line">Kepala Cabang</div>
        </div>
        <div class="signature-box">
            <div><?= date('d F Y') ?></div>
            <div class="signature-line">Petugas Pencetak</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
