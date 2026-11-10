<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Profil Saya';
$activeMenu = 'profil';

$stmtUser = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmtUser->execute([$u['id']]);
$userData = $stmtUser->fetch();

$errors = [];
$success = '';

// Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_profil') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $identitas_nik = trim($_POST['identitas_nik'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $no_hp = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');

    if ($nama === '') $errors[] = 'Nama lengkap tidak boleh kosong.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Alamat email tidak valid.';
    if ($no_hp === '') $errors[] = 'Nomor HP tidak boleh kosong.';

    // Cek duplikasi email dengan user lain
    if (empty($errors) && $email !== $userData['email']) {
        $stmtCek = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmtCek->execute([$email, $u['id']]);
        if ($stmtCek->fetch()) {
            $errors[] = 'Email ini sudah digunakan oleh akun lain.';
        }
    }

    if (empty($errors)) {
        $stmtUpd = db()->prepare('
            UPDATE users SET nama = ?, identitas_nik = ?, email = ?, no_hp = ?, jenis_kelamin = ?, tanggal_lahir = ?
            WHERE id = ?
        ');
        $stmtUpd->execute([$nama, $identitas_nik, $email, $no_hp, $jenis_kelamin, $tanggal_lahir, $u['id']]);

        // Perbarui data sesi
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['identitas_nik'] = $identitas_nik;
        $_SESSION['user']['no_hp'] = $no_hp;
        $_SESSION['user']['jenis_kelamin'] = $jenis_kelamin;

        $userData['nama'] = $nama;
        $userData['email'] = $email;
        $userData['identitas_nik'] = $identitas_nik;
        $userData['no_hp'] = $no_hp;
        $userData['jenis_kelamin'] = $jenis_kelamin;
        $userData['tanggal_lahir'] = $tanggal_lahir;

        flash('Profil berhasil diperbarui.', 'success');
        redirect('pasien/profil.php');
    }
}

// Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_password') {
    csrf_check();

    $oldPassword = $_POST['password_lama'] ?? '';
    $newPassword = $_POST['password_baru'] ?? '';
    $confirmPassword = $_POST['password_konfirmasi'] ?? '';

    $hash = $userData['password'] ?? $userData['password_hash'] ?? '';

    if (!password_verify($oldPassword, $hash)) {
        $errors[] = 'Password lama yang Anda masukkan salah.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Password baru minimal harus 8 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $u['id']]);
        flash('Password berhasil diubah. Silakan gunakan password baru pada sesi masuk berikutnya.', 'success');
        redirect('pasien/profil.php');
    }
}

require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="container container-narrow">
  <div style="margin-bottom: 24px;">
    <h2>Pengaturan Profil & Akun</h2>
    <p class="muted">Kelola data informasi identitas dan keamanan kata sandi Anda.</p>
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

  <!-- Card Informasi Pasien -->
  <div class="card" style="padding: 32px; margin-bottom: 28px;">
    <h3 style="margin-bottom: 20px; font-size: 1.25rem;">
      <i class="fa-solid fa-id-card text-primary"></i> Data Pribadi Pasien
    </h3>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="update_profil">

      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama" class="form-control" required value="<?= e($userData['nama']) ?>">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">NIK / NIM / Nomor Identitas</label>
          <input type="text" name="identitas_nik" class="form-control" value="<?= e($userData['identitas_nik']) ?>">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Jenis Kelamin</label>
          <select name="jenis_kelamin" class="form-control">
            <option value="">-- Pilih --</option>
            <option value="L" <?= $userData['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $userData['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Alamat Email</label>
          <input type="email" name="email" class="form-control" required value="<?= e($userData['email']) ?>">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor WhatsApp / HP</label>
          <input type="tel" name="no_hp" class="form-control" required value="<?= e($userData['no_hp']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir" class="form-control" value="<?= e($userData['tanggal_lahir']) ?>">
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
      </button>
    </form>
  </div>

  <!-- Card Ganti Password -->
  <div class="card" style="padding: 32px;">
    <h3 style="margin-bottom: 20px; font-size: 1.25rem;">
      <i class="fa-solid fa-lock text-primary"></i> Keamanan & Ganti Password
    </h3>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="update_password">

      <div class="form-group">
        <label class="form-label">Password Saat Ini (Lama)</label>
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
        <i class="fa-solid fa-key"></i> Perbarui Kata Sandi
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
