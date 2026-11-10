<?php
require_once 'config/config.php';
$pageTitle = 'Layanan - MentalCare';
$currentNav = 'layanan';

require 'includes/header.php';
?>

<div class="container" style="padding-top: 40px;">
  <div class="text-center" style="max-width: 750px; margin: 0 auto 40px;">
    <span class="badge badge-info" style="margin-bottom: 12px;">Solusi Menyeluruh</span>
    <h1>Layanan Kesehatan Mental Kami</h1>
    <p class="muted" style="font-size: 1.1rem;">
      Pendekatan terpadu mulai dari skrining mandiri, psikoedukasi, hingga sesi konsultasi psikologi privat.
    </p>
  </div>

  <div class="grid grid-3" style="margin-bottom: 40px;">
    <div class="card" style="padding: 30px;">
      <div class="feature-icon-wrapper">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>
      <h3>1. Mental Health Assessment</h3>
      <p class="muted">
        Skrining mandiri menggunakan instrumen psikometrik terpercaya untuk mengevaluasi:
      </p>
      <ul style="padding-left: 20px; margin-bottom: 20px; font-size: 0.9rem; color: var(--text-main);">
        <li>Tingkat Stres Harian</li>
        <li>Kecemasan Umum (GAD-7)</li>
        <li>Indikator Suasana Hati Depresif (PHQ-9)</li>
        <li>Kelelahan Kerja & Akademik (Burnout)</li>
        <li>Indeks Kesejahteraan Mental (Well-being)</li>
      </ul>
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-light btn-block btn-sm">Mulai Pemeriksaan</a>
    </div>

    <div class="card" style="padding: 30px;">
      <div class="feature-icon-wrapper" style="background: #e0f2fe; color: #0284c7;">
        <i class="fa-solid fa-headset"></i>
      </div>
      <h3>2. Konsultasi Psikolog Online</h3>
      <p class="muted">
        Konsultasi fleksibel dan privat dari kenyamanan ruang pribadi Anda melalui:
      </p>
      <ul style="padding-left: 20px; margin-bottom: 20px; font-size: 0.9rem; color: var(--text-main);">
        <li>Video Call terenkripsi</li>
        <li>Sesi interaktif 50-60 menit</li>
        <li>Tinjauan hasil skrining bersama psikolog</li>
        <li>Catatan dan tindak lanjut personal</li>
      </ul>
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-secondary btn-block btn-sm">Jadwalkan Konsultasi</a>
    </div>

    <div class="card" style="padding: 30px;">
      <div class="feature-icon-wrapper" style="background: #fef3c7; color: #d97706;">
        <i class="fa-solid fa-book-open-reader"></i>
      </div>
      <h3>3. Psikoedukasi & Panduan</h3>
      <p class="muted">
        Kumpulan artikel, tips praktis, dan panduan relaksasi yang dapat Anda terapkan sehari-hari:
      </p>
      <ul style="padding-left: 20px; margin-bottom: 20px; font-size: 0.9rem; color: var(--text-main);">
        <li>Teknik pernapasan & grounding</li>
        <li>Manajemen stres & kebersihan tidur</li>
        <li>Strategi regulasi emosi</li>
        <li>Mengenali tanda bahaya (red flags)</li>
      </ul>
      <a href="<?= url('artikel.php') ?>" class="btn btn-light btn-block btn-sm">Baca Artikel Edukasi</a>
    </div>
  </div>

  <div class="card" style="background: #fff; padding: 30px; border-left: 4px solid var(--primary);">
    <h3><i class="fa-solid fa-circle-question text-primary"></i> Kapan Sebaiknya Anda Berkonsultasi?</h3>
    <p class="muted" style="margin-top: 10px;">
      Jika Anda merasakan salah satu atau lebih tanda di bawah ini selama lebih dari 2 minggu berturut-turut:
    </p>
    <div class="grid grid-2" style="margin-top: 16px;">
      <ul class="recommendations-list">
        <li>Kesulitan tidur atau terbangun di tengah malam terus menerus.</li>
        <li>Perasaan cemas berlebihan dan jantung sering berdebar tanpa sebab.</li>
        <li>Kehilangan minat pada hal-hal yang dulu disukai.</li>
      </ul>
      <ul class="recommendations-list">
        <li>Mudah menangis, marah, atau merasa kewalahan dengan tugas rutin.</li>
        <li>Menarik diri dari keluarga, teman, dan interaksi sosial.</li>
        <li>Kondisi pikiran mulai mengganggu performa kerja atau kuliah.</li>
      </ul>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
