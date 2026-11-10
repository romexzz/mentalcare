<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$pageTitle = 'Profil & Kredensial Praktik';
$activeMenu = 'profil';

$stmtUser = db()->prepare('
    SELECT u.*, p.id as psikolog_id, p.nomor_izin, p.spesialisasi, p.pendidikan, p.pengalaman, p.deskripsi, p.status_verifikasi
    FROM users u
    LEFT JOIN psikolog p ON p.user_id = u.id
    WHERE u.id = ?
');
$stmtUser->execute([$u['id']]);
$user = $stmtUser->fetch();

$errors = [];

// Update Biodata & Kredensial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_profil') {
    csrf_check();

    $nama = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $no_hp = trim($_POST['no_hp'] ?? '');
    $nomor_izin = trim($_POST['nomor_izin'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '');
    $pendidikan = trim($_POST['pendidikan'] ?? '');
    $pengalaman = trim($_POST['pengalaman'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') $errors[] = 'Nama lengkap tidak boleh kosong.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Alamat email tidak valid.';

    // Cek email duplikat
    if (empty($errors) && $email !== $user['email']) {
        $stmtCek = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmtCek->execute([$email, $u['id']]);
        if ($stmtCek->fetch()) $errors[] = 'Email sudah digunakan akun lain.';
    }

    if (empty($errors)) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $pdo->prepare('UPDATE users SET nama = ?, email = ?, no_hp = ? WHERE id = ?')
                ->execute([$nama, $email, $no_hp, $u['id']]);

            if ($user['psikolog_id']) {
                $pdo->prepare('
                    UPDATE psikolog 
                    SET nomor_izin = ?, spesialisasi = ?, pendidikan = ?, pengalaman = ?, deskripsi = ?
                    WHERE id = ?
                ')->execute([$nomor_izin, $spesialisasi, $pendidikan, $pengalaman, $deskripsi, $user['psikolog_id']]);
            } else {
                $pdo->prepare('
                    INSERT INTO psikolog (user_id, nomor_izin, spesialisasi, pendidikan, pengalaman, deskripsi)
                    VALUES (?, ?, ?, ?, ?, ?)
                ')->execute([$u['id'], $nomor_izin, $spesialisasi, $pendidikan, $pengalaman, $deskripsi]);
            }

            $pdo->commit();
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            flash('Profil dan kredensial profesional berhasil disimpan.', 'success');
            redirect('psikolog/profil.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
        }
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
        $errors[] = 'Password lama tidak cocok.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $u['id']]);
        flash('Password berhasil diperbarui.', 'success');
        redirect('psikolog/profil.php');
    }
}

require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<div class="container container-narrow">
  <div style="margin-bottom: 24px;">
    <h2>Profil & Kredensial Praktik Psikolog</h2>
    <p class="muted">Informasi ini akan ditampilkan kepada calon pasien pada halaman publik dan kartu psikolog.</p>
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

  <div class="card" style="padding: 32px; margin-bottom: 28px;">
    <h3 style="margin-bottom: 20px; font-size: 1.25rem;">
      <i class="fa-solid fa-user-doctor text-primary"></i> Data Profesional
    </h3>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="update_profil">

      <div class="form-group">
        <label class="form-label">Nama Lengkap & Gelar</label>
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

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor SIPP / STR Praktik</label>
          <input type="text" name="nomor_izin" class="form-control" placeholder="Contoh: SIPP-1284-PSI-2020" value="<?= e($user['nomor_izin']) ?>">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Lama Pengalaman</label>
          <input type="text" name="pengalaman" class="form-control" placeholder="Contoh: 5 Tahun" value="<?= e($user['pengalaman']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Spesialisasi Klinis</label>
        <input type="text" name="spesialisasi" class="form-control" placeholder="Contoh: Stres Kerja, Kecemasan, Depresi, Mindfulness CBT" value="<?= e($user['spesialisasi']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Latar Belakang Pendidikan</label>
        <input type="text" name="pendidikan" class="form-control" placeholder="Contoh: S2 Magister Psikologi Profesi Klinis - Universitas Indonesia" value="<?= e($user['pendidikan']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Biografi Singkat & Pendekatan Konseling</label>
        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsikan pendekatan dan gaya konseling Anda..."><?= e($user['deskripsi']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Simpan Profil Profesional
      </button>
    </form>
  </div>

  <!-- Card Ubah Password -->
  <div class="card" style="padding: 32px;">
    <h3 style="margin-bottom: 20px; font-size: 1.25rem;">
      <i class="fa-solid fa-lock text-primary"></i> Ganti Password Akun
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
        <i class="fa-solid fa-key"></i> Perbarui Password
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
