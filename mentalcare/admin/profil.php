<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Profil Administrator';
$activeMenu = 'profil';

$stmtUser = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmtUser->execute([$u['id']]);
$user = $stmtUser->fetch();

$errors = [];

// Update Biodata
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_profil') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $no_hp = trim($_POST['no_hp'] ?? '');

    if ($nama === '') $errors[] = 'Nama administrator tidak boleh kosong.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Alamat email tidak valid.';

    if (empty($errors) && $email !== $user['email']) {
        $stmtCek = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmtCek->execute([$email, $u['id']]);
        if ($stmtCek->fetch()) $errors[] = 'Email ini sudah dipakai akun lain.';
    }

    if (empty($errors)) {
        db()->prepare('UPDATE users SET nama = ?, email = ?, no_hp = ? WHERE id = ?')
            ->execute([$nama, $email, $no_hp, $u['id']]);

        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;
        $user['nama'] = $nama;
        $user['email'] = $email;
        $user['no_hp'] = $no_hp;

        flash('Profil administrator berhasil diperbarui.', 'success');
        redirect('admin/profil.php');
    }
}

// Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_password') {
    csrf_check();

    $oldPassword = $_POST['password_lama'] ?? '';
    $newPassword = $_POST['password_baru'] ?? '';
    $confirmPassword = $_POST['password_konfirmasi'] ?? '';

    $hash = $user['password'] ?? $user['password_hash'] ?? '';

    if (!password_verify($oldPassword, $hash)) {
        $errors[] = 'Password saat ini salah.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Password baru minimal harus 8 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $u['id']]);
        flash('Password administrator berhasil diubah.', 'success');
        redirect('admin/profil.php');
    }
}

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="container container-narrow">
  <div style="margin-bottom: 24px;">
    <h2>Pengaturan Akun Administrator</h2>
    <p class="muted">Kelola kredensial login dan profil akun super admin.</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="flex-direction: column; align-items: flex-start;">
      <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 4px;">
        <i class="fa-solid fa-circle-exclamation"></i> Gagal Memperbarui:
      </div>
      <ul style="padding-left: 20px; margin: 0; font-size: 0.9rem;">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card" style="padding: 32px; margin-bottom: 24px;">
    <h3 style="margin-bottom: 20px; font-size: 1.25rem;">
      <i class="fa-solid fa-user-shield text-primary"></i> Data Administrator
    </h3>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="update_profil">

      <div class="form-group">
        <label class="form-label">Nama Lengkap Administrator</label>
        <input type="text" name="nama" class="form-control" required value="<?= e($user['nama']) ?>">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Alamat Email</label>
          <input type="email" name="email" class="form-control" required value="<?= e($user['email']) ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor WhatsApp / HP</label>
          <input type="tel" name="no_hp" class="form-control" value="<?= e($user['no_hp']) ?>">
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Simpan Biodata
      </button>
    </form>
  </div>

  <!-- Ganti Password -->
  <div class="card" style="padding: 32px;">
    <h3 style="margin-bottom: 20px; font-size: 1.25rem;">
      <i class="fa-solid fa-lock text-primary"></i> Ganti Password
    </h3>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="update_password">

      <div class="form-group">
        <label class="form-label">Password Saat Ini</label>
        <input type="password" name="password_lama" class="form-control" required placeholder="Masukkan password lama">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Password Baru</label>
          <input type="password" name="password_baru" class="form-control" required minlength="8" placeholder="Minimal 8 karakter">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Konfirmasi Password Baru</label>
          <input type="password" name="password_konfirmasi" class="form-control" required minlength="8" placeholder="Ulangi password baru">
        </div>
      </div>

      <button type="submit" class="btn btn-secondary">
        <i class="fa-solid fa-key"></i> Perbarui Password Admin
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
