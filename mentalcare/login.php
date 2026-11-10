<?php
require_once 'config/config.php';

// Jika sudah login, langsung arahkan ke dashboard yang sesuai
$curr = current_user();
if ($curr) {
    if ($curr['role'] === 'admin') redirect('admin/index.php');
    if ($curr['role'] === 'psikolog') redirect('psikolog/index.php');
    redirect('pasien/index.php');
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $err = 'Silakan isi email dan password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Dukung baik kolom 'password' maupun 'password_hash' untuk backward compatibility
        $hash = $user['password'] ?? $user['password_hash'] ?? '';

        if ($user && password_verify($password, $hash)) {
            // Cek status aktif
            if (isset($user['status']) && $user['status'] !== 'aktif') {
                $err = 'Akun Anda dinonaktifkan oleh Administrator. Hubungi bantuan jika ada kendala.';
            } else {
                // Simpan data sesi
                $_SESSION['user'] = [
                    'id'            => (int)$user['id'],
                    'nama'          => $user['nama'],
                    'email'         => $user['email'],
                    'role'          => $user['role'],
                    'foto'          => $user['foto'] ?? 'default.png',
                    'identitas_nik' => $user['identitas_nik'] ?? '',
                    'no_hp'         => $user['no_hp'] ?? '',
                    'jenis_kelamin' => $user['jenis_kelamin'] ?? ''
                ];

                flash('Selamat datang kembali, ' . $user['nama'] . '!', 'success');

                // Redirect sesuai Role
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                } elseif ($user['role'] === 'psikolog') {
                    redirect('psikolog/index.php');
                } else {
                    redirect('pasien/index.php');
                }
            }
        } else {
            $err = 'Email atau password yang Anda masukkan salah.';
        }
    }
}

$pageTitle = 'Masuk ke Akun Anda';
$currentNav = 'login';
require 'includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 40px; margin-bottom: 60px;">
  <div class="card" style="max-width: 480px; margin: 0 auto; padding: 36px;">
    <div class="text-center" style="margin-bottom: 24px;">
      <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px;">
        <i class="fa-solid fa-arrow-right-to-bracket"></i>
      </div>
      <h2>Masuk ke MentalCare</h2>
      <p class="muted" style="font-size: 0.9rem;">Akses dashboard pemeriksaan dan layanan konseling Anda</p>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= e($err) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label">Alamat Email</label>
        <div style="position: relative;">
          <input type="email" name="email" id="inputEmail" class="form-control" required placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" id="inputPassword" class="form-control" required placeholder="Masukkan password Anda">
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
      </button>
    </form>

    <!-- Tombol Cepat Demo (Quick Autofill Demo Accounts) -->
    <div style="margin-top: 28px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
      <p class="muted text-center" style="font-size: 0.8rem; font-weight: 700; margin-bottom: 10px; text-transform: uppercase;">
        ⚡ Akses Demo Cepat (1-Klik):
      </p>
      <div style="display: flex; gap: 6px; flex-direction: column;">
        <button type="button" class="btn btn-light btn-sm" onclick="setDemo('admin@mentalcare.test', 'Admin123!')">
          <i class="fa-solid fa-crown text-danger"></i> Demo Admin
        </button>
        <button type="button" class="btn btn-light btn-sm" onclick="setDemo('psikolog@mentalcare.test', 'Psikolog123!')">
          <i class="fa-solid fa-user-doctor text-info"></i> Demo Psikolog
        </button>
        <button type="button" class="btn btn-light btn-sm" onclick="setDemo('pasien@mentalcare.test', 'Pasien123!')">
          <i class="fa-solid fa-user text-primary"></i> Demo Pasien
        </button>
      </div>
    </div>

    <div class="text-center" style="margin-top: 24px; font-size: 0.925rem;">
      Belum memiliki akun? <a href="<?= url('register.php') ?>" style="font-weight: 700;">Daftar Pasien di Sini</a>
    </div>
  </div>
</div>

<script>
function setDemo(email, pass) {
  document.getElementById('inputEmail').value = email;
  document.getElementById('inputPassword').value = pass;
}
</script>

<?php require 'includes/footer.php'; ?>
