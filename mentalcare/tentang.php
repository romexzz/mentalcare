<?php
require_once 'config/config.php';
$pageTitle = 'Tentang Kami - Mengenal Platform MentalCare';
$currentNav = 'tentang';

require 'includes/header.php';
?>

<div class="container" style="padding-top: 40px;">
  <div class="text-center" style="max-width: 750px; margin: 0 auto 40px;">
    <span class="badge badge-success" style="margin-bottom: 12px;">Profil & Misi</span>
    <h1>Tentang MentalCare</h1>
    <p class="muted" style="font-size: 1.1rem;">
      Menghadirkan layanan kesehatan mental yang inklusif, ilmiah, dan berempati bagi seluruh masyarakat Indonesia.
    </p>
  </div>

  <div class="grid grid-2" style="margin-bottom: 40px; align-items: center;">
    <div class="card" style="padding: 32px;">
      <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-bullseye text-primary"></i> Visi Kami</h3>
      <p>
        Menjadi platform kesehatan mental terdepan yang mendobrak stigma seputar isu kejiwaan, memberikan ruang aman untuk berefleksi, serta memudahkan setiap individu mendapatkan bantuan psikologis berkualitas dan terjangkau.
      </p>
      <h3 style="margin: 24px 0 16px;"><i class="fa-solid fa-hand-holding-heart text-primary"></i> Nilai Utama</h3>
      <ul class="recommendations-list">
        <li><strong>Kerahasiaan & Keamanan Data:</strong> Perlindungan ketat pada data hasil skrining dan rekam medis pasien.</li>
        <li><strong>Berdasarkan Bukti Ilmiah (Evidence-Based):</strong> Alat ukur psikometrik terstandar (PHQ-9, GAD-7, DASS) dan praktik psikologi profesional.</li>
        <li><strong>Empati & Non-Judgemental:</strong> Setiap cerita dan keluhan diterima dengan penuh penghargaan tanpa stigma.</li>
      </ul>
    </div>

    <div class="card" style="padding: 32px; background: var(--primary-light); border-color: #cdebe5;">
      <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-user-shield text-primary"></i> Tim Psikolog Berlisensi</h3>
      <p>
        Seluruh psikolog mitra di MentalCare merupakan lulusan Magister Psikologi Profesi yang memiliki Surat Izin Praktik Psikologi (SIPP) dan Surat Tanda Registrasi (STR) aktif dari Himpunan Psikologi Indonesia (HIMPSI).
      </p>
      <div style="background: #fff; padding: 20px; border-radius: var(--radius-md); margin-top: 20px;">
        <h4 style="margin-bottom: 8px;"><i class="fa-solid fa-lock text-primary"></i> Jaminan Kerahasiaan</h4>
        <p class="muted" style="font-size: 0.9rem; margin: 0;">
          Sesuai Kode Etik Psikologi Indonesia dan standar privasi kesehatan, catatan sesi dan hasil kuisioner Anda tidak dapat diakses oleh pihak luar tanpa izin tertulis dari Anda.
        </p>
      </div>
    </div>
  </div>

  <div class="card text-center" style="padding: 40px 20px;">
    <h2>Ingin Berkonsultasi atau Memeriksa Kondisi?</h2>
    <p class="muted" style="max-width: 600px; margin: 10px auto 24px;">
      Jangan biarkan beban pikiran Anda menumpuk sendirian. Mulai dengan langkah kecil hari ini.
    </p>
    <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-primary"><i class="fa-solid fa-clipboard-check"></i> Mulai Pemeriksaan</a>
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-outline"><i class="fa-solid fa-user-doctor"></i> Lihat Psikolog Kami</a>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
