<?php
/**
 * admin/dashboard.php
 * Dashboard ringkasan statistik untuk role Admin.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Admin');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Dashboard Admin';
$activePage = 'dashboard';

// ── Statistik Utama ─────────────────────────────────────
$totalPeserta = $koneksi->query("SELECT COUNT(*) AS total FROM peserta")->fetch_assoc()['total'] ?? 0;
$totalKlaim   = $koneksi->query("SELECT COUNT(*) AS total FROM klaim")->fetch_assoc()['total'] ?? 0;
$totalUser    = $koneksi->query("SELECT COUNT(*) AS total FROM users WHERE role='Petugas'")->fetch_assoc()['total'] ?? 0;

// Klaim per status
$statusData = [];
$resStatus  = $koneksi->query(
    "SELECT status_klaim, COUNT(*) AS jml FROM klaim GROUP BY status_klaim"
);
while ($row = $resStatus->fetch_assoc()) {
    $statusData[$row['status_klaim']] = (int)$row['jml'];
}

// Klaim per jenis
$jenisData = [];
$resJenis  = $koneksi->query(
    "SELECT jenis_klaim, COUNT(*) AS jml FROM klaim GROUP BY jenis_klaim ORDER BY jml DESC"
);
while ($row = $resJenis->fetch_assoc()) {
    $jenisData[$row['jenis_klaim']] = (int)$row['jml'];
}

// 10 Klaim terbaru
$recentKlaim = $koneksi->query(
    "SELECT k.id_klaim, k.tgl_pengajuan, k.jenis_klaim, k.status_klaim,
            p.nama_peserta, u.nama_lengkap AS nama_petugas
     FROM klaim k
     JOIN peserta p ON k.no_kpj   = p.no_kpj
     JOIN users   u ON k.id_user  = u.id_user
     ORDER BY k.created_at DESC
     LIMIT 10"
);

require_once __DIR__ . '/../includes/header.php';

// Helper badge status
function badgeStatus(string $status): string {
    $map = [
        'Berkas Diterima' => 'badge-diterima',
        'Verifikasi'      => 'badge-verifikasi',
        'Disetujui'       => 'badge-disetujui',
        'Ditolak'         => 'badge-ditolak',
    ];
    $cls = $map[$status] ?? 'badge';
    return "<span class=\"badge {$cls}\">{$status}</span>";
}

function badgeJenis(string $jenis): string {
    $cls = 'badge badge-' . strtolower($jenis);
    return "<span class=\"{$cls}\">{$jenis}</span>";
}
?>

<!-- STATISTIK UTAMA -->
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-icon">&#9786;</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPeserta) ?></div>
            <div class="stat-label">Total Peserta Terdaftar</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">&#9654;</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalKlaim) ?></div>
            <div class="stat-label">Total Klaim Masuk</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon">&#9733;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $statusData['Verifikasi'] ?? 0 ?></div>
            <div class="stat-label">Sedang Diverifikasi</div>
        </div>
    </div>
    <div class="stat-card teal">
        <div class="stat-icon">&#10004;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $statusData['Disetujui'] ?? 0 ?></div>
            <div class="stat-label">Klaim Disetujui</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">&#10006;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $statusData['Ditolak'] ?? 0 ?></div>
            <div class="stat-label">Klaim Ditolak</div>
        </div>
    </div>
</div>

<!-- ROW: Klaim per Jenis + Status Summary -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

    <!-- Klaim per Jenis -->
    <div class="card">
        <div class="card-header">
            <h3>Klaim per Jenis</h3>
        </div>
        <div class="card-body">
            <?php
            $jenisLabels = [
                'JHT' => 'Jaminan Hari Tua',
                'JKK' => 'Jaminan Kecelakaan Kerja',
                'JKM' => 'Jaminan Kematian',
                'JP'  => 'Jaminan Pensiun',
            ];
            $jenisColors = [
                'JHT' => 'var(--green-primary)',
                'JKK' => 'var(--status-received)',
                'JKM' => '#9b59b6',
                'JP'  => '#f39c12',
            ];
            foreach (['JHT','JKK','JKM','JP'] as $j):
                $jml   = $jenisData[$j] ?? 0;
                $pct   = $totalKlaim > 0 ? round($jml / $totalKlaim * 100) : 0;
                $color = $jenisColors[$j];
            ?>
            <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                    <span style="font-size:.85rem;font-weight:600;color:var(--gray-700);">
                        <?= $j ?> &mdash; <?= $jenisLabels[$j] ?>
                    </span>
                    <span style="font-size:.85rem;font-weight:700;color:var(--gray-700);">
                        <?= $jml ?> <small style="color:var(--gray-400);">(<?= $pct ?>%)</small>
                    </span>
                </div>
                <div style="height:10px;background:var(--gray-200);border-radius:10px;overflow:hidden;">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:10px;transition:.6s ease;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Ringkasan Status -->
    <div class="card">
        <div class="card-header">
            <h3>Ringkasan Status Klaim</h3>
        </div>
        <div class="card-body">
            <?php
            $allStatus = [
                'Berkas Diterima' => ['color' => 'var(--status-received)', 'cls' => 'badge-diterima'],
                'Verifikasi'      => ['color' => 'var(--status-verify)',   'cls' => 'badge-verifikasi'],
                'Disetujui'       => ['color' => 'var(--status-approved)', 'cls' => 'badge-disetujui'],
                'Ditolak'         => ['color' => 'var(--status-rejected)', 'cls' => 'badge-ditolak'],
            ];
            foreach ($allStatus as $s => $cfg):
                $jml = $statusData[$s] ?? 0;
                $pct = $totalKlaim > 0 ? round($jml / $totalKlaim * 100) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-100);">
                <span class="badge <?= $cfg['cls'] ?>" style="min-width:130px;justify-content:center;"><?= $s ?></span>
                <div style="flex:1;height:10px;background:var(--gray-200);border-radius:10px;overflow:hidden;">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $cfg['color'] ?>;border-radius:10px;"></div>
                </div>
                <span style="font-size:.85rem;font-weight:700;color:var(--gray-700);min-width:36px;text-align:right;"><?= $jml ?></span>
            </div>
            <?php endforeach; ?>

            <div style="margin-top:14px;padding:10px 14px;background:var(--green-light);border-radius:var(--border-radius);display:flex;justify-content:space-between;">
                <span style="font-size:.875rem;font-weight:700;color:var(--green-dark);">Total Klaim</span>
                <span style="font-size:.875rem;font-weight:800;color:var(--green-dark);"><?= $totalKlaim ?></span>
            </div>
        </div>
    </div>

</div>

<!-- 10 Klaim Terbaru -->
<div class="card">
    <div class="card-header">
        <h3>10 Klaim Terbaru</h3>
        <a href="klaim.php" class="btn btn-outline btn-sm">Lihat Semua &rarr;</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Klaim</th>
                    <th>Peserta</th>
                    <th>Petugas</th>
                    <th>Tgl Pengajuan</th>
                    <th>Jenis</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if ($recentKlaim && $recentKlaim->num_rows > 0):
                    while ($k = $recentKlaim->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code style="font-size:.78rem;"><?= htmlspecialchars($k['id_klaim']) ?></code></td>
                    <td><?= htmlspecialchars($k['nama_peserta']) ?></td>
                    <td><?= htmlspecialchars($k['nama_petugas']) ?></td>
                    <td><?= date('d/m/Y', strtotime($k['tgl_pengajuan'])) ?></td>
                    <td><?= badgeJenis($k['jenis_klaim']) ?></td>
                    <td><?= badgeStatus($k['status_klaim']) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:30px;">Belum ada data klaim.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
