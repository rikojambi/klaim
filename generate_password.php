<?php
/**
 * generate_password.php
 * Script helper untuk generate hash password baru.
 * HAPUS file ini setelah digunakan di production!
 * Akses: http://localhost/sistem-klaim-bpjs/generate_password.php
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Generate Password Hash</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }
        input, button { padding: 8px 12px; margin: 6px 0; width: 100%; box-sizing: border-box; }
        code { display: block; background: #f0f0f0; padding: 12px; word-break: break-all;
               border-radius: 4px; margin-top: 12px; font-size: 13px; }
        h2 { color: #008752; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 12px;
                   border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
<h2>&#128274; Generator Hash Password</h2>
<div class="warning">
    &#9888; <strong>PERINGATAN:</strong> Hapus file ini setelah digunakan! Jangan biarkan file ini ada di server production.
</div>
<form method="POST">
    <label>Password yang ingin di-hash:</label>
    <input type="text" name="password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" placeholder="Masukkan password">
    <button type="submit">Generate Hash</button>
</form>
<?php if (!empty($_POST['password'])): ?>
<p>Hash untuk <strong>"<?= htmlspecialchars($_POST['password']) ?>"</strong>:</p>
<code><?= password_hash($_POST['password'], PASSWORD_DEFAULT) ?></code>
<p style="font-size:13px;color:#555;">Salin hash ini dan gunakan untuk UPDATE tabel <code>users</code>.</p>
<?php endif; ?>
</body>
</html>
