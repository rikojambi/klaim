<?php
/**
 * petugas/klaim_tambah.php
 * Form perekaman klaim baru + upload dokumen berkas.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Petugas');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Rekam Klaim Baru';
$activePage = 'klaim_tambah';
$idUser     = (int)$_SESSION['id_user'];

$alert = [];
if (!empty($_GET['sukses'])) {
    $alert = ['type'=>'success','msg'=>'Klaim berhasil direkam! ID Klaim: <strong>' . htmlspecialchars($_GET['sukses']) . '</strong>'];
}
if (!empty($_GET['error'])) {
    $alert = ['type'=>'danger','msg'=>urldecode($_GET['error'])];
}

/* ── Generate ID Klaim otomatis ───────────────────────────────── */
function generateIdKlaim(mysqli $db): string {
    $prefix = 'KLM-' . date('Ymd') . '-';
    $stmt   = $db->prepare(
        "SELECT id_klaim FROM klaim WHERE id_klaim LIKE ? ORDER BY id_klaim DESC LIMIT 1"
    );
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($last) {
        $lastNum = (int)substr($last['id_klaim'], -3);
        return $prefix . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    }
    return $prefix . '001';
}

/* ── PROSES POST ──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_kpj      = trim($_POST['no_kpj']      ?? '');
    $jenis_klaim = $_POST['jenis_klaim']       ?? '';
    $tgl         = $_POST['tgl_pengajuan']     ?? date('Y-m-d');
    $keterangan  = trim($_POST['keterangan']   ?? '');
    $id_klaim    = generateIdKlaim($koneksi);

    $err = '';
    if (!$no_kpj || !$jenis_klaim) {
        $err = 'Peserta dan jenis klaim wajib diisi.';
    } elseif (!in_array($jenis_klaim, ['JHT','JKK','JKM','JP'])) {
        $err = 'Jenis klaim tidak valid.';
    }

    // ── Upload file dokumen ────────────────────────────────────
    $uploadedFiles = [];
    $uploadDir     = __DIR__ . '/../assets/uploads/';

    if (!$err && !empty($_FILES['dokumen']['name'][0])) {
        $allowedMime   = ['application/pdf','image/jpeg','image/jpg','image/png'];
        $allowedExt    = ['pdf','jpg','jpeg','png'];
        $maxSize       = 2 * 1024 * 1024; // 2 MB
        $dokNames      = $_POST['nama_dokumen'] ?? [];

        foreach ($_FILES['dokumen']['name'] as $idx => $namaFile) {
            if (empty($namaFile)) continue;

            $tmpPath  = $_FILES['dokumen']['tmp_name'][$idx];
            $fileSize = $_FILES['dokumen']['size'][$idx];
            $fileErr  = $_FILES['dokumen']['error'][$idx];
            $ext      = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            $mime     = mime_content_type($tmpPath);
            $namaDok  = trim($dokNames[$idx] ?? 'Dokumen ' . ($idx + 1));

            if ($fileErr !== UPLOAD_ERR_OK) {
                $err = "Error upload file '{$namaFile}'. Kode: {$fileErr}"; break;
            }
            if ($fileSize > $maxSize) {
                $err = "File '{$namaFile}' melebihi batas 2 MB."; break;
            }
            if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
                $err = "File '{$namaFile}' tidak diizinkan. Hanya PDF, JPG, PNG."; break;
            }

            // Buat nama file unik
            $newName  = $id_klaim . '_' . ($idx + 1) . '_' . uniqid() . '.' . $ext;
            $destPath = $uploadDir . $newName;

            if (move_uploaded_file($tmpPath, $destPath)) {
                $uploadedFiles[] = ['nama' => $namaDok, 'path' => 'assets/uploads/' . $newName];
            } else {
                $err = "Gagal menyimpan file '{$namaFile}'. Periksa izin folder uploads."; break;
            }
        }
    }

    if (!$err) {
        // Simpan klaim
        $status = 'Berkas Diterima';
        $stmt   = $koneksi->prepare(
            "INSERT INTO klaim (id_klaim, no_kpj, id_user, tgl_pengajuan, jenis_klaim, status_klaim, keterangan)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('sssisss', $id_klaim, $no_kpj, $idUser, $tgl, $jenis_klaim, $status, $keterangan);

        if ($stmt->execute()) {
            $stmt->close();
            // Simpan dokumen
            foreach ($uploadedFiles as $dok) {
                $dstmt = $koneksi->prepare(
                    "INSERT INTO dokumen_syarat (id_klaim, nama_dokumen, file_path) VALUES (?,?,?)"
                );
                $dstmt->bind_param('sss', $id_klaim, $dok['nama'], $dok['path']);
                $dstmt->execute();
                $dstmt->close();
            }
            header('Location: klaim_tambah.php?sukses=' . urlencode($id_klaim));
            exit;
        } else {
            $err = 'Gagal menyimpan klaim: ' . $koneksi->error;
            $stmt->close();
        }
    }

    if ($err) {
        // Hapus file yang sudah terlanjur di-upload jika ada error DB
        foreach ($uploadedFiles as $dok) {
            $fp = __DIR__ . '/../' . $dok['path'];
            if (file_exists($fp)) unlink($fp);
        }
        header('Location: klaim_tambah.php?error=' . urlencode($err));
        exit;
    }
}

/* ── Ambil daftar peserta ─────────────────────────────────────── */
$daftarPeserta = $koneksi->query(
    "SELECT no_kpj, nama_peserta, nama_perusahaan FROM peserta ORDER BY nama_peserta ASC"
);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($alert): ?>
<div class="alert alert-<?= $alert['type'] ?>" style="margin-bottom:16px;">
    <?= $alert['msg'] ?>
</div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h2>Rekam Klaim Baru</h2>
        <p class="breadcrumb">Petugas &rsaquo; <span>Rekam Klaim</span></p>
    </div>
    <a href="klaim_status.php" class="btn btn-outline">&#9654; Lihat Status Klaim</a>
</div>

<div class="info-box" style="margin-bottom:20px;">
    <strong>&#8505; Petunjuk:</strong> Isi data klaim dengan lengkap. Upload dokumen berkas pendukung (PDF/JPG/PNG, maks. 2 MB per file).
    ID Klaim akan digenerate otomatis oleh sistem.
</div>

<form action="klaim_tambah.php" method="POST" enctype="multipart/form-data" id="formKlaim">

    <!-- SECTION 1: Data Klaim -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3>&#9654; Data Klaim</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="no_kpj">Peserta (No. KPJ)</label>
                    <select id="no_kpj" name="no_kpj" class="form-control" required>
                        <option value="">-- Pilih Peserta --</option>
                        <?php while ($p = $daftarPeserta->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($p['no_kpj']) ?>">
                            <?= htmlspecialchars($p['no_kpj']) ?> &mdash; <?= htmlspecialchars($p['nama_peserta']) ?>
                            (<?= htmlspecialchars($p['nama_perusahaan']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="file-hint">Peserta belum terdaftar?
                        <a href="peserta.php" target="_blank">Tambah di halaman Peserta</a>
                    </p>
                </div>
                <div class="form-group">
                    <label for="jenis_klaim">Jenis Klaim</label>
                    <select id="jenis_klaim" name="jenis_klaim" class="form-control" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="JHT">JHT &mdash; Jaminan Hari Tua</option>
                        <option value="JKK">JKK &mdash; Jaminan Kecelakaan Kerja</option>
                        <option value="JKM">JKM &mdash; Jaminan Kematian</option>
                        <option value="JP">JP &mdash; Jaminan Pensiun</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tgl_pengajuan">Tanggal Pengajuan</label>
                    <input type="date" id="tgl_pengajuan" name="tgl_pengajuan"
                        class="form-control" value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Status Awal</label>
                    <input type="text" class="form-control" value="Berkas Diterima" readonly
                        style="background:var(--gray-100);cursor:not-allowed;">
                    <p class="file-hint">Status awal klaim selalu "Berkas Diterima".</p>
                </div>
            </div>
            <div class="form-group">
                <label for="keterangan">Keterangan / Catatan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"
                    placeholder="Catatan tambahan terkait pengajuan klaim (opsional)" rows="3"></textarea>
            </div>
        </div>
    </div>

    <!-- SECTION 2: Upload Dokumen -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3>&#128196; Upload Dokumen Berkas</h3>
            <span class="text-muted" style="font-size:.8rem;">PDF, JPG, PNG | Maks. 2 MB per file</span>
        </div>
        <div class="card-body">
            <div id="dokumenContainer">
                <!-- Row dokumen pertama -->
                <div class="dokumen-row" id="dok-0" style="display:grid;grid-template-columns:1fr 2fr auto;gap:12px;align-items:flex-end;margin-bottom:12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Nama Dokumen</label>
                        <input type="text" name="nama_dokumen[]" class="form-control"
                            placeholder="Cth: KTP, BPJS Card" >
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>File Dokumen</label>
                        <input type="file" name="dokumen[]" class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div style="padding-bottom:2px;">
                        <button type="button" class="btn btn-outline-gray btn-sm" disabled>&#8722;</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="btnTambahDok"
                style="margin-top:8px;">
                &#43; Tambah Dokumen
            </button>
            <p class="file-hint" style="margin-top:8px;">
                Maksimal 5 file dokumen per pengajuan. Dokumen bersifat opsional namun sangat disarankan dilampirkan.
            </p>
        </div>
    </div>

    <!-- Tombol Submit -->
    <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="klaim_status.php" class="btn btn-outline-gray">Batal</a>
        <button type="submit" class="btn btn-primary btn-lg">
            &#10003; Simpan Klaim
        </button>
    </div>

</form>

<script>
var dokumenCount = 1;
var maxDokumen   = 5;

document.getElementById('btnTambahDok').addEventListener('click', function () {
    if (dokumenCount >= maxDokumen) {
        alert('Maksimal ' + maxDokumen + ' dokumen per klaim.');
        return;
    }
    var idx = dokumenCount++;
    var container = document.getElementById('dokumenContainer');
    var row = document.createElement('div');
    row.className = 'dokumen-row';
    row.id = 'dok-' + idx;
    row.style.cssText = 'display:grid;grid-template-columns:1fr 2fr auto;gap:12px;align-items:flex-end;margin-bottom:12px;';
    row.innerHTML = `
        <div class="form-group" style="margin-bottom:0;">
            <label>Nama Dokumen</label>
            <input type="text" name="nama_dokumen[]" class="form-control" placeholder="Nama dokumen">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>File Dokumen</label>
            <input type="file" name="dokumen[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div style="padding-bottom:2px;">
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusDok('dok-${idx}')">&#8722;</button>
        </div>
    `;
    container.appendChild(row);
});

function hapusDok(id) {
    var el = document.getElementById(id);
    if (el) { el.remove(); dokumenCount--; }
}

// Validasi ukuran file sebelum submit
document.getElementById('formKlaim').addEventListener('submit', function(e) {
    var files = document.querySelectorAll('input[type="file"]');
    for (var i = 0; i < files.length; i++) {
        if (files[i].files[0] && files[i].files[0].size > 2 * 1024 * 1024) {
            alert('File "' + files[i].files[0].name + '" melebihi batas 2 MB!');
            e.preventDefault(); return;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
