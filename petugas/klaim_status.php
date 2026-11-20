<?php
/**
 * petugas/klaim_status.php
 * Tracking & update status klaim milik petugas yang login.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Petugas');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Status Klaim';
$activePage = 'klaim_status';
$idUser     = (int)$_SESSION['id_user'];

$alert = [];
if (!empty($_GET['sukses'])) {
    $alert = ['type'=>'success','msg'=>'Status klaim berhasil diperbarui.'];
}
if (!empty($_GET['error'])) {
    $alert = ['type'=>'danger','msg'=>urldecode($_GET['error'])];
}

/* ── PROSES UPDATE STATUS ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_klaim    = trim($_POST['id_klaim']    ?? '');
    $status_baru = trim($_POST['status_klaim'] ?? '');
    $keterangan  = trim($_POST['keterangan']   ?? '');
    $validStatus = ['Berkas Diterima','Verifikasi','Disetujui','Ditolak'];

    if (!$id_klaim || !in_array($status_baru, $validStatus)) {
        header('Location: klaim_status.php?error='.urlencode('Data tidak valid.'));
        exit;
    }

    // Pastikan klaim ini milik petugas yang login
    $cek = $koneksi->prepare("SELECT id_klaim FROM klaim WHERE id_klaim=? AND id_user=?");
    $cek->bind_param('si', $id_klaim, $idUser);
    $cek->execute();
    $klaimAda = $cek->get_result()->num_rows > 0;
    $cek->close();

    if (!$klaimAda) {
        header('Location: klaim_status.php?error='.urlencode('Klaim tidak ditemukan atau bukan milik Anda.'));
        exit;
    }

    $stmt = $koneksi->prepare(
        "UPDATE klaim SET status_klaim=?, keterangan=? WHERE id_klaim=? AND id_user=?"
    );
    $stmt->bind_param('sssi', $status_baru, $keterangan, $id_klaim, $idUser);
    if ($stmt->execute()) {
        header('Location: klaim_status.php?sukses=1');
    } else {
        header('Location: klaim_status.php?error='.urlencode('Gagal update: '.$koneksi->error));
    }
    $stmt->close();
    exit;
}

/* ── Filter ──────────────────────────────────────────────────── */
$f_jenis  = $_GET['jenis']  ?? '';
$f_status = $_GET['status'] ?? '';
$f_cari   = trim($_GET['cari'] ?? '');

$where  = ['k.id_user = ?'];
$params = [$idUser];
$types  = 'i';

if ($f_jenis && in_array($f_jenis, ['JHT','JKK','JKM','JP'])) {
    $where[] = 'k.jenis_klaim = ?'; $params[] = $f_jenis; $types .= 's';
}
if ($f_status && in_array($f_status, ['Berkas Diterima','Verifikasi','Disetujui','Ditolak'])) {
    $where[] = 'k.status_klaim = ?'; $params[] = $f_status; $types .= 's';
}
if ($f_cari) {
    $like = '%'.$f_cari.'%';
    $where[] = '(k.id_klaim LIKE ? OR p.nama_peserta LIKE ?)';
    $params[] = $like; $params[] = $like; $types .= 'ss';
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT k.id_klaim, k.tgl_pengajuan, k.jenis_klaim, k.status_klaim, k.keterangan,
               p.nama_peserta, p.nama_perusahaan, p.no_telp,
               (SELECT COUNT(*) FROM dokumen_syarat WHERE id_klaim=k.id_klaim) AS jml_dok
        FROM klaim k
        JOIN peserta p ON k.no_kpj = p.no_kpj
        {$whereClause}
        ORDER BY k.tgl_pengajuan DESC, k.id_klaim DESC";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$daftarKlaim = $stmt->get_result();
$stmt->close();

require_once __DIR__ . '/../includes/header.php';

function bs($s) {
    $map=['Berkas Diterima'=>'badge-diterima','Verifikasi'=>'badge-verifikasi','Disetujui'=>'badge-disetujui','Ditolak'=>'badge-ditolak'];
    return '<span class="badge '.($map[$s]??'badge').'">'.htmlspecialchars($s).'</span>';
}
function bj($j) { return '<span class="badge badge-'.strtolower($j).'">'.htmlspecialchars($j).'</span>'; }
?>

<?php if ($alert): ?>
<div class="alert alert-<?= $alert['type'] ?>" style="margin-bottom:16px;"><?= $alert['msg'] ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h2>Status & Tracking Klaim</h2>
        <p class="breadcrumb">Petugas &rsaquo; <span>Status Klaim</span></p>
    </div>
    <a href="klaim_tambah.php" class="btn btn-primary">&#43; Rekam Klaim Baru</a>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar">
    <div class="form-group">
        <label>Cari</label>
        <input type="text" name="cari" class="form-control"
            placeholder="ID Klaim / Nama Peserta" value="<?= htmlspecialchars($f_cari) ?>">
    </div>
    <div class="form-group">
        <label>Jenis</label>
        <select name="jenis" class="form-control">
            <option value="">Semua</option>
            <?php foreach (['JHT','JKK','JKM','JP'] as $j): ?>
            <option value="<?= $j ?>" <?= $f_jenis===$j?'selected':'' ?>><?= $j ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="">Semua</option>
            <?php foreach (['Berkas Diterima','Verifikasi','Disetujui','Ditolak'] as $s): ?>
            <option value="<?= $s ?>" <?= $f_status===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="flex:0;">
        <label>&nbsp;</label>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="klaim_status.php" class="btn btn-outline-gray">Reset</a>
        </div>
    </div>
</form>

<!-- Tabel Klaim -->
<div class="card">
    <div class="card-header">
        <h3>Daftar Klaim Saya</h3>
        <span class="text-muted" style="font-size:.8rem;"><?= $daftarKlaim->num_rows ?> data ditemukan</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Klaim</th>
                    <th>Peserta</th>
                    <th>Perusahaan</th>
                    <th>Tgl Pengajuan</th>
                    <th>Jenis</th>
                    <th>Dokumen</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if ($daftarKlaim->num_rows > 0):
                    while ($k = $daftarKlaim->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code style="font-size:.73rem;"><?= htmlspecialchars($k['id_klaim']) ?></code></td>
                    <td><?= htmlspecialchars($k['nama_peserta']) ?></td>
                    <td style="font-size:.8rem;color:var(--gray-500);"><?= htmlspecialchars($k['nama_perusahaan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($k['tgl_pengajuan'])) ?></td>
                    <td><?= bj($k['jenis_klaim']) ?></td>
                    <td>
                        <?php if ($k['jml_dok'] > 0): ?>
                        <span class="badge badge-disetujui" style="cursor:pointer;"
                            onclick="lihatDokumen('<?= htmlspecialchars($k['id_klaim']) ?>')">
                            &#128196; <?= $k['jml_dok'] ?> file
                        </span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.78rem;">Tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td><?= bs($k['status_klaim']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm"
                            onclick="bukaUpdate(
                                '<?= htmlspecialchars(addslashes($k['id_klaim'])) ?>',
                                '<?= htmlspecialchars(addslashes($k['nama_peserta'])) ?>',
                                '<?= htmlspecialchars(addslashes($k['jenis_klaim'])) ?>',
                                '<?= htmlspecialchars(addslashes($k['status_klaim'])) ?>',
                                '<?= htmlspecialchars(addslashes($k['keterangan'] ?? '')) ?>'
                            )">
                            Update Status
                        </button>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <div class="empty-icon">&#9654;</div>
                            <p>Belum ada klaim yang direkam. <a href="klaim_tambah.php">Rekam klaim pertama Anda</a>.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL: UPDATE STATUS KLAIM
     ============================================================ -->
<div class="modal-overlay" id="modalUpdate">
    <div class="modal-box" style="max-width:500px;">
        <div class="modal-header">
            <h3>Update Status Klaim</h3>
            <button class="modal-close" onclick="tutupModal('modalUpdate')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id_klaim" id="u_id_klaim">
            <div class="modal-body">
                <div class="info-box" style="margin-bottom:4px;">
                    <strong>ID Klaim:</strong> <span id="u_id_disp" style="font-family:monospace;"></span><br>
                    <strong>Peserta:</strong> <span id="u_peserta"></span> |
                    <strong>Jenis:</strong> <span id="u_jenis"></span>
                </div>

                <div class="form-group">
                    <label for="u_status">Status Baru</label>
                    <select id="u_status" name="status_klaim" class="form-control" required>
                        <option value="Berkas Diterima">Berkas Diterima</option>
                        <option value="Verifikasi">Verifikasi</option>
                        <option value="Disetujui">Disetujui</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="u_keterangan">Catatan / Keterangan</label>
                    <textarea id="u_keterangan" name="keterangan" class="form-control"
                        placeholder="Alasan perubahan status atau catatan tambahan..."
                        rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-gray" onclick="tutupModal('modalUpdate')">Batal</button>
                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: LIHAT DOKUMEN
     ============================================================ -->
<div class="modal-overlay" id="modalDokumen">
    <div class="modal-box" style="max-width:500px;">
        <div class="modal-header">
            <h3>&#128196; Dokumen Klaim</h3>
            <button class="modal-close" onclick="tutupModal('modalDokumen')">&#10005;</button>
        </div>
        <div class="modal-body" id="dokumenList">
            <p class="text-muted">Memuat...</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline-gray" onclick="tutupModal('modalDokumen')">Tutup</button>
        </div>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.add('open'); }
function tutupModal(id) { document.getElementById(id).classList.remove('open'); }

function bukaUpdate(id, peserta, jenis, status, keterangan) {
    document.getElementById('u_id_klaim').value  = id;
    document.getElementById('u_id_disp').textContent  = id;
    document.getElementById('u_peserta').textContent  = peserta;
    document.getElementById('u_jenis').textContent    = jenis;
    document.getElementById('u_status').value         = status;
    document.getElementById('u_keterangan').value     = keterangan;
    bukaModal('modalUpdate');
}

function lihatDokumen(idKlaim) {
    var listEl = document.getElementById('dokumenList');
    listEl.innerHTML = '<p class="text-muted">Memuat dokumen...</p>';
    bukaModal('modalDokumen');

    // Fetch dokumen via AJAX inline (tanpa framework)
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '<?= getBasePath() ?>/petugas/get_dokumen.php?id_klaim=' + encodeURIComponent(idKlaim));
    xhr.onload = function() {
        if (xhr.status === 200) {
            listEl.innerHTML = xhr.responseText;
        } else {
            listEl.innerHTML = '<p class="text-muted">Gagal memuat dokumen.</p>';
        }
    };
    xhr.onerror = function() {
        listEl.innerHTML = '<p class="text-muted">Koneksi gagal.</p>';
    };
    xhr.send();
}

document.querySelectorAll('.modal-overlay').forEach(function(el){
    el.addEventListener('click', function(e){ if(e.target===el) el.classList.remove('open'); });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
