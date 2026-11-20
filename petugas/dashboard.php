<?php
/**
 * petugas/dashboard.php
 * Dashboard untuk role Petugas — statistik klaim milik petugas yg login.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Petugas');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Dashboard Petugas';
$activePage = 'dashboard';
$idUser     = (int)$_SESSION['id_user'];

// ── Statistik ─────────────────────────────────────────────────
$stmtTotal = $koneksi->prepare("SELECT COUNT(*) AS total FROM klaim WHERE id_user=?");
$stmtTotal->bind_param('i', $idUser);
$stmtTotal->execute();
$totalKlaim = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;
$stmtTotal->close();

$totalPeserta = $koneksi->query("SELECT COUNT(*) AS total FROM peserta")->fetch_assoc()['total'] ?? 0;

// Klaim per status milik petugas ini
$statusData = [];
$stmtStatus = $koneksi->prepare(
    "SELECT status_klaim, COUNT(*) AS jml FROM klaim WHERE id_user=? GROUP BY status_klaim"
);
$stmtStatus->bind_param('i', $idUser);
$stmtStatus->execute();
$resStatus = $stmtStatus->get_result();
while ($row = $resStatus->fetch_assoc()) {
    $statusData[$row['status_klaim']] = (int)$row['jml'];
}
$stmtStatus->close();

// Klaim per jenis milik petugas ini
$jenisData = [];
$stmtJenis = $koneksi->prepare(
    "SELECT jenis_klaim, COUNT(*) AS jml FROM klaim WHERE id_user=? GROUP BY jenis_klaim"
);
$stmtJenis->bind_param('i', $idUser);
$stmtJenis->execute();
$resJenis = $stmtJenis->get_result();
while ($row = $resJenis->fetch_assoc()) {
    $jenisData[$row['jenis_klaim']] = (int)$row['jml'];
}
$stmtJenis->close();

// 8 Klaim terbaru milik petugas ini
$stmtRecent = $koneksi->prepare(
    "SELECT k.id_klaim, k.tgl_pengajuan, k.jenis_klaim, k.status_klaim,
            p.nama_peserta
     FROM klaim k
     JOIN peserta p ON k.no_kpj = p.no_kpj
     WHERE k.id_user = ?
     ORDER BY k.created_at DESC
     LIMIT 8"
);
$stmtRecent->bind_param('i', $idUser);
$stmtRecent->execute();
$recentKlaim = $stmtRecent->get_result();
$stmtRecent->close();

require_once __DIR__ . '/../includes/header.php';

function bs($s) {
    $map=['Berkas Diterima'=>'badge-diterima','Verifikasi'=>'badge-verifikasi','Disetujui'=>'badge-disetujui','Ditolak'=>'badge-ditolak'];
    return '<span class="badge '.($map[$s]??'badge').'">'.htmlspecialchars($s).'</span>';
}
function bj($j) { return '<span class="badge badge-'.strtolower($j).'">'.htmlspecialchars($j).'</span>'; }
?>

<!-- Greeting -->
<div style="background:linear-gradient(135deg,var(--green-primary),var(--green-accent));border-radius:var(--border-radius);padding:24px 28px;margin-bottom:24px;color:#fff;">
    <h2 style="font-size:1.2rem;margin-bottom:4px;">
        Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!
    </h2>
    <p style="opacity:.85;font-size:.875rem;">
        <?= date('l, d F Y') ?> &mdash; Kelola klaim BPJS Ketenagakerjaan dengan mudah dan efisien.
    </p>
</div>

<!-- STATS -->
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-icon">&#9654;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalKlaim ?></div>
            <div class="stat-label">Total Klaim Saya</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">&#9786;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalPeserta ?></div>
            <div class="stat-label">Total Peserta</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon">&#9203;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $statusData['Berkas Diterima'] ?? 0 ?></div>
            <div class="stat-label">Berkas Diterima</div>
        </div>
    </div>
    <div class="stat-card teal">
        <div class="stat-icon">&#10004;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $statusData['Disetujui'] ?? 0 ?></div>
            <div class="stat-label">Klaim Disetujui</div>
        </div>
    </div>
</div>

<!-- ROW: Jenis Klaim + Quick Actions -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">

    <!-- Klaim Terbaru -->
    <div class="card">
        <div class="card-header">
            <h3>Klaim Terbaru Saya</h3>
            <a href="klaim_status.php" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID Klaim</th>
                        <th>Peserta</th>
                        <th>Tgl</th>
                        <th>Jenis</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hasRow = false;
                    while ($k = $recentKlaim->fetch_assoc()):
                        $hasRow = true;
                    ?>
                    <tr>
                        <td><code style="font-size:.73rem;"><?= htmlspecialchars($k['id_klaim']) ?></code></td>
                        <td><?= htmlspecialchars($k['nama_peserta']) ?></td>
                        <td><?= date('d/m/Y', strtotime($k['tgl_pengajuan'])) ?></td>
                        <td><?= bj($k['jenis_klaim']) ?></td>
                        <td><?= bs($k['status_klaim']) ?></td>
                    </tr>
                    <?php endwhile;
                    if (!$hasRow): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:24px;">Belum ada klaim.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions & Distribusi Jenis -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header"><h3>Aksi Cepat</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                <a href="klaim_tambah.php" class="btn btn-primary btn-block">&#43; Rekam Klaim Baru</a>
                <a href="peserta.php"      class="btn btn-secondary btn-block">&#9786; Kelola Peserta</a>
                <a href="klaim_status.php" class="btn btn-outline btn-block">&#9654; Update Status Klaim</a>
            </div>
        </div>

        <!-- Distribusi Jenis -->
        <div class="card">
            <div class="card-header"><h3>Distribusi Jenis</h3></div>
            <div class="card-body">
                <?php foreach (['JHT','JKK','JKM','JP'] as $j):
                    $jml = $jenisData[$j] ?? 0;
                    $pct = $totalKlaim > 0 ? round($jml / $totalKlaim * 100) : 0;
                    $colors = ['JHT'=>'var(--green-primary)','JKK'=>'var(--status-received)','JKM'=>'#9b59b6','JP'=>'#f39c12'];
                ?>
                <div style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:3px;">
                        <span><?= $j ?></span><span><?= $jml ?></span>
                    </div>
                    <div style="height:8px;background:var(--gray-200);border-radius:8px;overflow:hidden;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $colors[$j] ?>;border-radius:8px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
