<?php
require_once 'config/config.php';
$pageTitle = 'Kontak & Hotline Bantuan';
$currentNav = 'kontak';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if ($nama && filter_var($email, FILTER_VALIDATE_EMAIL) && $pesan) {
        flash('Terima kasih. Pesan Anda telah kami terima, tim kami akan segera menghubungi Anda melalui email.', 'success');
        redirect('kontak.php');
    } else {
        $msg = 'Harap isi semua kolom dengan benar.';
    }
}

require 'includes/header.php';
?>

<div class="container" style="padding-top: 40px; margin-bottom: 60px;">
  <div class="text-center" style="max-width: 700px; margin: 0 auto 40px;">
    <span class="badge badge-success" style="margin-bottom: 12px;">Pusat Bantuan</span>
    <h1>Hubungi Kami</h1>
    <p class="muted">
      Kami siap mendengar. Silakan hubungi kami untuk pertanyaan seputar layanan atau jika Anda memerlukan informasi darurat.
    </p>
  </div>

  <div class="grid grid-2">
    <!-- Form Kirim Pesan -->
    <div class="card" style="padding: 32px;">
      <h3 style="margin-bottom: 16px;"><i class="fa-regular fa-envelope text-primary"></i> Kirimkan Pesan</h3>
      <?php if ($msg): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= e($msg) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" required placeholder="Contoh: Siti Rahmawati">
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Email</label>
          <input type="email" name="email" class="form-control" required placeholder="nama@email.com">
        </div>

        <div class="form-group">
          <label class="form-label">Pesan atau Pertanyaan</label>
          <textarea name="pesan" class="form-control" rows="5" required placeholder="Tuliskan pesan Anda..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          <i class="fa-solid fa-paper-plane"></i> Kirim Pesan
        </button>
      </form>
    </div>

    <!-- Informasi Kontak & Hotline Krisis -->
    <div>
      <div class="card" style="padding: 32px; background: #fff5f5; border: 1.5px solid #fecaca; margin-bottom: 24px;">
        <h3 style="color: #dc2626; margin-bottom: 12px;">
          <i class="fa-solid fa-triangle-exclamation"></i> Bantuan Krisis & Darurat
        </h3>
        <p style="font-size: 0.95rem; color: #7f1d1d; margin-bottom: 16px;">
          Jika Anda atau seseorang yang Anda kenal berada dalam bahaya, mengalami krisis kejiwaan, atau memiliki dorongan melukai diri sendiri, mohon segera hubungi saluran bantuan berikut:
        </p>
        
        <div style="background: #fff; padding: 16px; border-radius: var(--radius-sm); margin-bottom: 12px; border-left: 4px solid #dc2626;">
          <strong>Layanan Sejiwa (Kemenkes RI):</strong><br>
          <span style="font-size: 1.25rem; font-weight: 800; color: #dc2626;">Telepon 119 Ext 8</span><br>
          <span class="muted" style="font-size: 0.85rem;">(Bebas pulsa, beroperasi 24 jam)</span>
        </div>

        <div style="background: #fff; padding: 16px; border-radius: var(--radius-sm); border-left: 4px solid #dc2626;">
          <strong>LISA Helpline (Bunuh Diri & Krisis):</strong><br>
          <span style="font-size: 1.15rem; font-weight: 700; color: #dc2626;">0811-3815-472</span>
        </div>
      </div>

      <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-location-dot text-primary"></i> Kantor & Operasional</h3>
        <p class="muted" style="margin-bottom: 8px;"><strong>Alamat:</strong> Jl. Kesehatan Mental No. 12, Jakarta Selatan, Indonesia</p>
        <p class="muted" style="margin-bottom: 8px;"><strong>Email Resmi:</strong> dukungan@mentalcare.test</p>
        <p class="muted" style="margin-bottom: 8px;"><strong>Jam Layanan Admin:</strong> Senin - Sabtu, 08.00 - 20.00 WIB</p>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
