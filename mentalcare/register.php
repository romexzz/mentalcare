<?php
require_once 'config/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$nama = '';
$identitas_nik = '';
$email = '';
$no_hp = '';
$jenis_kelamin = '';
$tanggal_lahir = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $identitas_nik = trim($_POST['identitas_nik'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $no_hp = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $consent = !empty($_POST['consent']);

    // Validasi
    if ($nama === '' || mb_strlen($nama) > 120) {
        $errors[] = 'Nama lengkap wajib diisi (maksimal 120 karakter).';
    }
    if ($identitas_nik === '') {
        $errors[] = 'NIK / NIM / Nomor Identitas wajib diisi.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format alamat email tidak valid.';
    }
    if ($no_hp === '') {
        $errors[] = 'Nomor WhatsApp / HP wajib diisi.';
    }
    if (!in_array($jenis_kelamin, ['L', 'P'])) {
        $errors[] = 'Pilih jenis kelamin.';
    }
    if (!$tanggal_lahir) {
        $errors[] = 'Tanggal lahir wajib diisi.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal harus 8 karakter.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
    if (!$consent) {
        $errors[] = 'Anda harus menyetujui syarat layanan & kebijakan privasi.';
    }

    // Cek duplikasi email
    if (empty($errors)) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk.';
        }
    }

    // Eksekusi Pendaftaran
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('
            INSERT INTO users (nama, identitas_nik, email, password, role, no_hp, tanggal_lahir, jenis_kelamin, status, consent_at)
            VALUES (?, ?, ?, ?, "pasien", ?, ?, ?, "aktif", NOW())
        ');
        $stmt->execute([
            $nama,
            $identitas_nik,
            $email,
            $hash,
            $no_hp,
            $tanggal_lahir,
            $jenis_kelamin
        ]);

        $newUserId = db()->lastInsertId();

        // Login otomatis setelah registrasi
        $_SESSION['user'] = [
            'id'            => (int)$newUserId,
            'nama'          => $nama,
            'email'         => $email,
            'role'          => 'pasien',
            'foto'          => 'default.png',
            'identitas_nik' => $identitas_nik,
            'no_hp'         => $no_hp,
            'jenis_kelamin' => $jenis_kelamin
        ];

        flash('Pendaftaran berhasil! Selamat datang di platform MentalCare.', 'success');
        redirect('pasien/index.php');
    }
}

$pageTitle = 'Pendaftaran Pasien Baru';
$currentNav = 'register';
require 'includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 40px; margin-bottom: 60px;">
  <div class="card" style="max-width: 620px; margin: 0 auto; padding: 36px;">
    <div class="text-center" style="margin-bottom: 24px;">
      <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px;">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h2>Daftar Akun Pasien</h2>
      <p class="muted" style="font-size: 0.9rem;">Buat akun untuk melakukan pemeriksaan kondisi kesehatan mental dan konseling</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="flex-direction: column; align-items: flex-start;">
        <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 4px;">
          <i class="fa-solid fa-circle-exclamation"></i> Terjadi Kesalahan:
        </div>
        <ul style="padding-left: 20px; margin: 0; font-size: 0.9rem;">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Siti Rahmawati" value="<?= e($nama) ?>">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">NIK / NIM / Nomor Identitas <span class="text-danger">*</span></label>
          <input type="text" name="identitas_nik" class="form-control" required placeholder="16 digit NIK atau NIM Anda" value="<?= e($identitas_nik) ?>">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
          <select name="jenis_kelamin" class="form-control" required>
            <option value="">-- Pilih --</option>
            <option value="L" <?= $jenis_kelamin === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $jenis_kelamin === 'P' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Alamat Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required placeholder="nama@email.com" value="<?= e($email) ?>">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
          <input type="tel" name="no_hp" class="form-control" required placeholder="081234567890" value="<?= e($no_hp) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
        <input type="date" name="tanggal_lahir" class="form-control" required value="<?= e($tanggal_lahir) ?>">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimal 8 karakter">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
          <input type="password" name="password_confirm" class="form-control" required minlength="8" placeholder="Ulangi password">
        </div>
      </div>

      <div style="background: var(--bg-body); padding: 14px; border-radius: var(--radius-sm); margin: 18px 0;">
        <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer; font-size: 0.875rem; font-weight: 500; margin: 0;">
          <input type="checkbox" name="consent" value="1" required style="width: auto; margin-top: 4px;">
          <span>
            Saya memahami bahwa pemeriksaan di MentalCare adalah skrining awal mandiri dan <strong>bukan pengganti diagnosis resmi profesional</strong>. Saya menyetujui pemrosesan data saya dengan jaminan kerahasiaan.
          </span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fa-solid fa-user-check"></i> Daftar Akun Pasien
      </button>
    </form>

    <div class="text-center" style="margin-top: 24px; font-size: 0.925rem;">
      Sudah memiliki akun? <a href="<?= url('login.php') ?>" style="font-weight: 700;">Masuk di Sini</a>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
