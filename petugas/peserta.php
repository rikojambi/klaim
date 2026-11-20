<?php
/**
 * petugas/peserta.php
 * CRUD Data Peserta BPJS untuk role Petugas.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Petugas');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Kelola Data Peserta';
$activePage = 'peserta';
$alert      = [];

/* ── Flash messages ─────────────────────────────────────────── */
if (!empty($_GET['sukses'])) {
    $pesan = [
        'tambah' => 'Data peserta berhasil ditambahkan.',
        'edit'   => 'Data peserta berhasil diperbarui.',
        'hapus'  => 'Data peserta berhasil dihapus.',
    ];
    $alert = ['type'=>'success','msg'=>$pesan[$_GET['sukses']] ?? 'Berhasil.'];
}
if (!empty($_GET['error'])) {
    $alert = ['type'=>'danger','msg'=>urldecode($_GET['error'])];
}

/* ── PROSES POST ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    /* ---------- TAMBAH ---------- */
    if ($aksi === 'tambah') {
        $no_kpj    = trim($_POST['no_kpj']          ?? '');
        $nik       = trim($_POST['nik']              ?? '');
        $nama      = trim($_POST['nama_peserta']     ?? '');
        $perusahaan= trim($_POST['nama_perusahaan']  ?? '');
        $telp      = trim($_POST['no_telp']          ?? '');

        if (!$no_kpj || !$nik || !$nama || !$perusahaan || !$telp) {
            $err = 'Semua field wajib diisi.';
        } elseif (!ctype_digit($no_kpj) || strlen($no_kpj) < 10 || strlen($no_kpj) > 20) {
            $err = 'No. KPJ harus angka, 10-20 digit.';
        } elseif (!ctype_digit($nik) || strlen($nik) !== 16) {
            $err = 'NIK harus 16 digit angka.';
        } else {
            $stmt = $koneksi->prepare(
                "INSERT INTO peserta (no_kpj, nik, nama_peserta, nama_perusahaan, no_telp)
                 VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param('sssss', $no_kpj, $nik, $nama, $perusahaan, $telp);
            if ($stmt->execute()) {
                header('Location: peserta.php?sukses=tambah'); exit;
            } else {
                $err = ($koneksi->errno === 1062)
                    ? "No. KPJ '{$no_kpj}' sudah terdaftar."
                    : 'Gagal menyimpan: ' . $koneksi->error;
            }
            $stmt->close();
        }
        if (isset($err)) { header('Location: peserta.php?error='.urlencode($err)); exit; }
    }

    /* ---------- EDIT ---------- */
    if ($aksi === 'edit') {
        $no_kpj    = trim($_POST['no_kpj']          ?? '');
        $nik       = trim($_POST['nik']              ?? '');
        $nama      = trim($_POST['nama_peserta']     ?? '');
        $perusahaan= trim($_POST['nama_perusahaan']  ?? '');
        $telp      = trim($_POST['no_telp']          ?? '');

        if (!$no_kpj || !$nama) { header('Location: peserta.php?error='.urlencode('Data tidak lengkap.')); exit; }

        $stmt = $koneksi->prepare(
            "UPDATE peserta SET nik=?, nama_peserta=?, nama_perusahaan=?, no_telp=?
             WHERE no_kpj=?"
        );
        $stmt->bind_param('sssss', $nik, $nama, $perusahaan, $telp, $no_kpj);
        $stmt->execute(); $stmt->close();
        header('Location: peserta.php?sukses=edit'); exit;
    }

    /* ---------- HAPUS ---------- */
    if ($aksi === 'hapus') {
        $no_kpj = trim($_POST['no_kpj'] ?? '');
        // Cek apakah peserta punya klaim
        $cek = $koneksi->prepare("SELECT COUNT(*) AS jml FROM klaim WHERE no_kpj=?");
        $cek->bind_param('s', $no_kpj);
        $cek->execute();
        $jml = $cek->get_result()->fetch_assoc()['jml'];
        $cek->close();

        if ($jml > 0) {
            header('Location: peserta.php?error='.urlencode("Peserta ini memiliki {$jml} klaim. Hapus klaim terlebih dahulu.")); exit;
        }

        $stmt = $koneksi->prepare("DELETE FROM peserta WHERE no_kpj=?");
        $stmt->bind_param('s', $no_kpj);
        $stmt->execute(); $stmt->close();
        header('Location: peserta.php?sukses=hapus'); exit;
    }
}

/* ── Cari & Ambil Data ────────────────────────────────────────── */
$cari = trim($_GET['cari'] ?? '');
if ($cari) {
    $like = '%' . $cari . '%';
    $stmt = $koneksi->prepare(
        "SELECT * FROM peserta
         WHERE no_kpj LIKE ? OR nik LIKE ? OR nama_peserta LIKE ? OR nama_perusahaan LIKE ?
         ORDER BY nama_peserta ASC"
    );
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $daftar = $stmt->get_result();
    $stmt->close();
} else {
    $daftar = $koneksi->query("SELECT * FROM peserta ORDER BY nama_peserta ASC");
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($alert): ?>
<div class="alert alert-<?= $alert['type'] ?>" style="margin-bottom:16px;"><?= htmlspecialchars($alert['msg']) ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h2>Data Peserta BPJS</h2>
        <p class="breadcrumb">Petugas &rsaquo; <span>Data Peserta</span></p>
    </div>
    <button class="btn btn-primary" onclick="bukaModal('modalTambah')">&#43; Tambah Peserta</button>
</div>

<!-- Form Cari -->
<form method="GET" class="filter-bar">
    <div class="form-group" style="flex:2;">
        <label>Cari Peserta</label>
        <input type="text" name="cari" class="form-control"
            placeholder="Nama, No. KPJ, NIK, atau Perusahaan"
            value="<?= htmlspecialchars($cari) ?>">
    </div>
    <div class="form-group" style="flex:0;">
        <label>&nbsp;</label>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="peserta.php" class="btn btn-outline-gray">Reset</a>
        </div>
    </div>
</form>

<!-- Tabel Peserta -->
<div class="card">
    <div class="card-header">
        <h3>Daftar Peserta</h3>
        <span class="text-muted" style="font-size:.8rem;">
            <?= $cari ? "Hasil pencarian: '{$cari}'" : "Semua peserta" ?>
        </span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>No. KPJ</th>
                    <th>NIK</th>
                    <th>Nama Peserta</th>
                    <th>Nama Perusahaan</th>
                    <th>No. Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if ($daftar && $daftar->num_rows > 0):
                    while ($p = $daftar->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code><?= htmlspecialchars($p['no_kpj']) ?></code></td>
                    <td><?= htmlspecialchars($p['nik']) ?></td>
                    <td><?= htmlspecialchars($p['nama_peserta']) ?></td>
                    <td><?= htmlspecialchars($p['nama_perusahaan']) ?></td>
                    <td><?= htmlspecialchars($p['no_telp']) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-warning btn-sm"
                                onclick="bukaEdit(
                                    '<?= htmlspecialchars(addslashes($p['no_kpj'])) ?>',
                                    '<?= htmlspecialchars(addslashes($p['nik'])) ?>',
                                    '<?= htmlspecialchars(addslashes($p['nama_peserta'])) ?>',
                                    '<?= htmlspecialchars(addslashes($p['nama_perusahaan'])) ?>',
                                    '<?= htmlspecialchars(addslashes($p['no_telp'])) ?>'
                                )">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="aksi"   value="hapus">
                                <input type="hidden" name="no_kpj" value="<?= htmlspecialchars($p['no_kpj']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Yakin hapus peserta '<?= htmlspecialchars(addslashes($p['nama_peserta'])) ?>'?">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon">&#9786;</div>
                            <p><?= $cari ? "Tidak ditemukan peserta dengan kata kunci '{$cari}'." : "Belum ada data peserta." ?></p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL: TAMBAH PESERTA
     ============================================================ -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3>&#43; Tambah Peserta Baru</h3>
            <button class="modal-close" onclick="tutupModal('modalTambah')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="t_kpj">No. KPJ <small class="text-muted">(10-20 digit angka)</small></label>
                        <input type="text" id="t_kpj" name="no_kpj" class="form-control"
                            placeholder="Cth: 1234567890001" required maxlength="20" pattern="\d{10,20}">
                    </div>
                    <div class="form-group">
                        <label for="t_nik">NIK <small class="text-muted">(16 digit)</small></label>
                        <input type="text" id="t_nik" name="nik" class="form-control"
                            placeholder="16 digit NIK" required maxlength="16" pattern="\d{16}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="t_nama">Nama Peserta</label>
                    <input type="text" id="t_nama" name="nama_peserta" class="form-control"
                        placeholder="Nama lengkap peserta" required>
                </div>
                <div class="form-group">
                    <label for="t_perusahaan">Nama Perusahaan</label>
                    <input type="text" id="t_perusahaan" name="nama_perusahaan" class="form-control"
                        placeholder="Nama perusahaan tempat bekerja" required>
                </div>
                <div class="form-group">
                    <label for="t_telp">No. Telepon</label>
                    <input type="tel" id="t_telp" name="no_telp" class="form-control"
                        placeholder="Cth: 081234567890" required maxlength="15">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-gray" onclick="tutupModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Peserta</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: EDIT PESERTA
     ============================================================ -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <h3>Edit Data Peserta</h3>
            <button class="modal-close" onclick="tutupModal('modalEdit')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi"   value="edit">
            <input type="hidden" name="no_kpj" id="e_kpj">
            <div class="modal-body">
                <div class="form-group">
                    <label>No. KPJ</label>
                    <input type="text" id="e_kpj_disp" class="form-control" readonly
                        style="background:var(--gray-100);cursor:not-allowed;">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="e_nik">NIK</label>
                        <input type="text" id="e_nik" name="nik" class="form-control"
                            required maxlength="16" pattern="\d{16}">
                    </div>
                    <div class="form-group">
                        <label for="e_nama">Nama Peserta</label>
                        <input type="text" id="e_nama" name="nama_peserta" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="e_perusahaan">Nama Perusahaan</label>
                    <input type="text" id="e_perusahaan" name="nama_perusahaan" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="e_telp">No. Telepon</label>
                    <input type="tel" id="e_telp" name="no_telp" class="form-control" required maxlength="15">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-gray" onclick="tutupModal('modalEdit')">Batal</button>
                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.add('open'); }
function tutupModal(id) { document.getElementById(id).classList.remove('open'); }
function bukaEdit(kpj, nik, nama, perusahaan, telp) {
    document.getElementById('e_kpj').value        = kpj;
    document.getElementById('e_kpj_disp').value   = kpj;
    document.getElementById('e_nik').value         = nik;
    document.getElementById('e_nama').value        = nama;
    document.getElementById('e_perusahaan').value  = perusahaan;
    document.getElementById('e_telp').value        = telp;
    bukaModal('modalEdit');
}
document.querySelectorAll('.modal-overlay').forEach(function(el){
    el.addEventListener('click', function(e){ if(e.target===el) el.classList.remove('open'); });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
