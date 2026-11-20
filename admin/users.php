<?php
/**
 * admin/users.php
 * Manajemen User (CRUD) — hanya Admin yang bisa mengakses.
 * Menampilkan daftar user, form tambah, edit (modal), dan hapus.
 */

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekRole('Admin');
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = 'Manajemen User';
$activePage = 'users';
$alert      = [];

/* ── Helper: pesan flash dari GET ────────────────────────────── */
if (!empty($_GET['sukses'])) {
    $pesan = [
        'tambah'  => 'User baru berhasil ditambahkan.',
        'edit'    => 'Data user berhasil diperbarui.',
        'hapus'   => 'User berhasil dihapus.',
        'reset_pw'=> 'Password user berhasil direset.',
    ];
    $alert = ['type' => 'success', 'msg' => $pesan[$_GET['sukses']] ?? 'Operasi berhasil.'];
}
if (!empty($_GET['error'])) {
    $alert = ['type' => 'danger', 'msg' => urldecode($_GET['error'])];
}

/* ── PROSES POST (Tambah / Edit / Hapus) ─────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    /* ---------- TAMBAH USER ---------- */
    if ($aksi === 'tambah') {
        $username  = trim($_POST['username'] ?? '');
        $nama      = trim($_POST['nama_lengkap'] ?? '');
        $role      = $_POST['role'] ?? 'Petugas';
        $password  = trim($_POST['password'] ?? '');
        $password2 = trim($_POST['password2'] ?? '');

        if (!$username || !$nama || !$password) {
            $err = 'Username, nama lengkap, dan password wajib diisi.';
        } elseif (strlen($password) < 6) {
            $err = 'Password minimal 6 karakter.';
        } elseif ($password !== $password2) {
            $err = 'Konfirmasi password tidak cocok.';
        } elseif (!in_array($role, ['Admin','Petugas'])) {
            $err = 'Role tidak valid.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare(
                "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?,?,?,?)"
            );
            $stmt->bind_param('ssss', $username, $hash, $nama, $role);
            if ($stmt->execute()) {
                header('Location: users.php?sukses=tambah');
                exit;
            } else {
                $err = ($koneksi->errno === 1062)
                    ? "Username '{$username}' sudah digunakan."
                    : 'Gagal menyimpan data: ' . $koneksi->error;
            }
            $stmt->close();
        }
        if (isset($err)) {
            header('Location: users.php?error=' . urlencode($err));
            exit;
        }
    }

    /* ---------- EDIT USER ---------- */
    if ($aksi === 'edit') {
        $id_user  = (int)($_POST['id_user'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama_lengkap'] ?? '');
        $role     = $_POST['role'] ?? 'Petugas';

        // Jangan ubah role admin satu-satunya
        if (!$id_user || !$username || !$nama) {
            header('Location: users.php?error=' . urlencode('Data tidak lengkap.'));
            exit;
        }

        $stmt = $koneksi->prepare(
            "UPDATE users SET username=?, nama_lengkap=?, role=? WHERE id_user=?"
        );
        $stmt->bind_param('sssi', $username, $nama, $role, $id_user);
        if ($stmt->execute()) {
            header('Location: users.php?sukses=edit');
            exit;
        } else {
            $err = ($koneksi->errno === 1062)
                ? "Username '{$username}' sudah digunakan."
                : 'Gagal memperbarui: ' . $koneksi->error;
            header('Location: users.php?error=' . urlencode($err));
            exit;
        }
        $stmt->close();
    }

    /* ---------- RESET PASSWORD ---------- */
    if ($aksi === 'reset_pw') {
        $id_user  = (int)($_POST['id_user'] ?? 0);
        $pw_baru  = trim($_POST['pw_baru'] ?? '');
        if (!$id_user || strlen($pw_baru) < 6) {
            header('Location: users.php?error=' . urlencode('Password baru minimal 6 karakter.'));
            exit;
        }
        $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE users SET password=? WHERE id_user=?");
        $stmt->bind_param('si', $hash, $id_user);
        $stmt->execute();
        $stmt->close();
        header('Location: users.php?sukses=reset_pw');
        exit;
    }

    /* ---------- HAPUS USER ---------- */
    if ($aksi === 'hapus') {
        $id_user = (int)($_POST['id_user'] ?? 0);
        // Jangan hapus diri sendiri
        if ($id_user === (int)$_SESSION['id_user']) {
            header('Location: users.php?error=' . urlencode('Anda tidak dapat menghapus akun Anda sendiri.'));
            exit;
        }
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id_user=?");
        $stmt->bind_param('i', $id_user);
        $stmt->execute();
        $stmt->close();
        header('Location: users.php?sukses=hapus');
        exit;
    }
}

/* ── Ambil semua user ─────────────────────────────────────────── */
$daftarUser = $koneksi->query(
    "SELECT id_user, username, nama_lengkap, role, created_at
     FROM users ORDER BY role ASC, nama_lengkap ASC"
);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Alert -->
<?php if ($alert): ?>
<div class="alert alert-<?= $alert['type'] ?>" style="margin-bottom:16px;"><?= htmlspecialchars($alert['msg']) ?></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2>Manajemen User</h2>
        <p class="breadcrumb">Admin &rsaquo; <span>Manajemen User</span></p>
    </div>
    <button class="btn btn-primary" onclick="bukaModal('modalTambah')">&#43; Tambah User</button>
</div>

<!-- Tabel User -->
<div class="card">
    <div class="card-header">
        <h3>Daftar Akun Pengguna</h3>
        <span class="text-muted" style="font-size:.8rem;">Total: <?= $daftarUser->num_rows ?> akun</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($u = $daftarUser->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower($u['role']) ?>">
                            <?= htmlspecialchars($u['role']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <!-- Tombol Edit -->
                            <button class="btn btn-warning btn-sm"
                                onclick="bukaEdit(
                                    <?= $u['id_user'] ?>,
                                    '<?= htmlspecialchars(addslashes($u['username'])) ?>',
                                    '<?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>',
                                    '<?= $u['role'] ?>'
                                )">Edit</button>

                            <!-- Tombol Reset PW -->
                            <button class="btn btn-outline btn-sm"
                                onclick="bukaResetPw(<?= $u['id_user'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                Reset PW
                            </button>

                            <!-- Tombol Hapus -->
                            <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="aksi"    value="hapus">
                                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Yakin hapus user '<?= htmlspecialchars(addslashes($u['username'])) ?>'? Semua klaim yang dibuat akan tetap ada.">
                                    Hapus
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:.75rem;">(Akun Anda)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL: TAMBAH USER
     ============================================================ -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-box">
        <div class="modal-header">
            <h3>&#43; Tambah User Baru</h3>
            <button class="modal-close" onclick="tutupModal('modalTambah')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="modal-body">
                <div class="form-group">
                    <label for="t_username">Username</label>
                    <input type="text" id="t_username" name="username" class="form-control"
                        placeholder="Contoh: petugas2" required>
                </div>
                <div class="form-group">
                    <label for="t_nama">Nama Lengkap</label>
                    <input type="text" id="t_nama" name="nama_lengkap" class="form-control"
                        placeholder="Nama lengkap pengguna" required>
                </div>
                <div class="form-group">
                    <label for="t_role">Role</label>
                    <select id="t_role" name="role" class="form-control">
                        <option value="Petugas">Petugas</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="t_pw">Password</label>
                    <input type="password" id="t_pw" name="password" class="form-control"
                        placeholder="Min. 6 karakter" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="t_pw2">Konfirmasi Password</label>
                    <input type="password" id="t_pw2" name="password2" class="form-control"
                        placeholder="Ulangi password" required minlength="6">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-gray" onclick="tutupModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: EDIT USER
     ============================================================ -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit Data User</h3>
            <button class="modal-close" onclick="tutupModal('modalEdit')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id_user" id="e_id_user">
            <div class="modal-body">
                <div class="form-group">
                    <label for="e_username">Username</label>
                    <input type="text" id="e_username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="e_nama">Nama Lengkap</label>
                    <input type="text" id="e_nama" name="nama_lengkap" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="e_role">Role</label>
                    <select id="e_role" name="role" class="form-control">
                        <option value="Petugas">Petugas</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-gray" onclick="tutupModal('modalEdit')">Batal</button>
                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: RESET PASSWORD
     ============================================================ -->
<div class="modal-overlay" id="modalResetPw">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Reset Password User</h3>
            <button class="modal-close" onclick="tutupModal('modalResetPw')">&#10005;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="aksi" value="reset_pw">
            <input type="hidden" name="id_user" id="rp_id_user">
            <div class="modal-body">
                <div class="info-box" style="margin-bottom:4px;">
                    Reset password untuk: <strong id="rp_username"></strong>
                </div>
                <div class="form-group">
                    <label for="rp_pw">Password Baru</label>
                    <input type="password" id="rp_pw" name="pw_baru" class="form-control"
                        placeholder="Min. 6 karakter" required minlength="6">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-gray" onclick="tutupModal('modalResetPw')">Batal</button>
                <button type="submit" class="btn btn-secondary">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) {
    document.getElementById(id).classList.add('open');
}
function tutupModal(id) {
    document.getElementById(id).classList.remove('open');
}
function bukaEdit(idUser, username, nama, role) {
    document.getElementById('e_id_user').value = idUser;
    document.getElementById('e_username').value = username;
    document.getElementById('e_nama').value = nama;
    document.getElementById('e_role').value = role;
    bukaModal('modalEdit');
}
function bukaResetPw(idUser, username) {
    document.getElementById('rp_id_user').value = idUser;
    document.getElementById('rp_username').textContent = username;
    document.getElementById('rp_pw').value = '';
    bukaModal('modalResetPw');
}
// Tutup modal saat klik overlay
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
