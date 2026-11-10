<?php
require_once 'config/config.php';
$pageTitle = 'Beranda - Layanan Kesehatan Mental';
$currentNav = 'home';

// Ambil artikel edukasi terbaru
$stmt = db()->query('SELECT a.*, u.nama as nama_penulis FROM artikel a LEFT JOIN users u ON u.id = a.penulis_id ORDER BY a.created_at DESC LIMIT 3');
$articles = $stmt->fetchAll();

// Ambil kategori assessment aktif
$stmtKat = db()->query("SELECT * FROM kategori_assessment WHERE status = 'aktif' ORDER BY id ASC LIMIT 4");
$categories = $stmtKat->fetchAll();

require 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container hero-content">
    <div class="hero-badge">
      <i class="fa-solid fa-heart-circle-check"></i> Ruang Aman & Nyaman Untukmu
    </div>
    <h1 class="hero-title">Jaga Kesehatan Mentalmu, Mulai dari Sekarang.</h1>
    <p class="hero-subtext">
      Kenali kondisi kesehatan mentalmu melalui pemeriksaan awal dan dapatkan rekomendasi untuk langkah selanjutnya.
    </p>
    <div class="hero-actions">
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-clipboard-check"></i> Mulai Pemeriksaan
      </a>
      <a href="<?= url('layanan.php') ?>" class="btn btn-outline btn-lg">
        <i class="fa-solid fa-circle-info"></i> Pelajari Lebih Lanjut
      </a>
    </div>
  </div>
</section>

<!-- 4 Main Cards Section (Sesuai Permintaan Spesifikasi) -->
<section class="container" style="margin-top: -30px; position: relative; z-index: 10;">
  <div class="grid grid-4">
    <!-- Card 1: Pemeriksaan Mental -->
    <div class="feature-card">
      <div class="feature-icon-wrapper">
        <i class="fa-solid fa-clipboard-list"></i>
      </div>
      <h3>Pemeriksaan Mental</h3>
      <p>Kuisioner terstandar untuk mengukur tingkat stres, kecemasan, depresi, burnout, dan kesejahteraan mental secara mandiri.</p>
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-light btn-sm">
        Mulai Skrining <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <!-- Card 2: Konsultasi Psikolog -->
    <div class="feature-card">
      <div class="feature-icon-wrapper" style="background:#e0f2fe; color:#0284c7;">
        <i class="fa-solid fa-user-doctor"></i>
      </div>
      <h3>Konsultasi Psikolog</h3>
      <p>Jadwalkan sesi konseling profesional baik secara online maupun tatap muka dengan psikolog klinis berlisensi resmi.</p>
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-light btn-sm">
        Pilih Psikolog <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <!-- Card 3: Riwayat Pemeriksaan -->
    <div class="feature-card">
      <div class="feature-icon-wrapper" style="background:#fef3c7; color:#d97706;">
        <i class="fa-solid fa-chart-line"></i>
      </div>
      <h3>Riwayat Pemeriksaan</h3>
      <p>Pantau perkembangan kondisi kesehatan emosional Anda dari waktu ke waktu melalui arsip rekam skrining yang aman.</p>
      <a href="<?= url('pasien/riwayat.php') ?>" class="btn btn-light btn-sm">
        Lihat Riwayat <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <!-- Card 4: Rekomendasi Kesehatan Mental -->
    <div class="feature-card">
      <div class="feature-icon-wrapper" style="background:#f3e8ff; color:#9333ea;">
        <i class="fa-solid fa-spa"></i>
      </div>
      <h3>Rekomendasi Mental</h3>
      <p>Dapatkan saran praktis, panduan kebersihan tidur, teknik relaksasi, dan edukasi penanganan awal yang tepat untuk Anda.</p>
      <a href="<?= url('artikel.php') ?>" class="btn btn-light btn-sm">
        Baca Panduan <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- Alur 4 Langkah Sederhana -->
<section class="container" style="margin-top: 60px;">
  <div class="text-center" style="max-width: 650px; margin: 0 auto 40px;">
    <h2>Alur Pemeriksaan & Layanan</h2>
    <p class="muted">Langkah mudah untuk memahami kondisi emosional Anda dan mendapatkan bantuan yang tepat.</p>
  </div>

  <div class="grid grid-4">
    <div class="card text-center">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-weight:700;">1</div>
      <h4>Pilih Assessment</h4>
      <p class="muted" style="font-size: 0.9rem;">Pilih kuisioner seperti Stres, Kecemasan, Depresi, atau Burnout sesuai keluhan Anda.</p>
    </div>

    <div class="card text-center">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-weight:700;">2</div>
      <h4>Isi Kuisioner</h4>
      <p class="muted" style="font-size: 0.9rem;">Jawab pertanyaan dengan jujur berdasarkan apa yang Anda rasakan dalam 2 minggu terakhir.</p>
    </div>

    <div class="card text-center">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-weight:700;">3</div>
      <h4>Lihat Hasil & Saran</h4>
      <p class="muted" style="font-size: 0.9rem;">Sistem menghitung total skor dan menyajikan tingkat risiko serta rekomendasi awal.</p>
    </div>

    <div class="card text-center">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-weight:700;">4</div>
      <h4>Konsultasi Psikolog</h4>
      <p class="muted" style="font-size: 0.9rem;">Bila kondisi membutuhkan penanganan lebih lanjut, jadwalkan sesi bersama psikolog berlisensi.</p>
    </div>
  </div>
</section>

<!-- Edukasi & Artikel Pilihan -->
<section class="container" style="margin-top: 50px;">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div>
      <h2>Informasi & Edukasi Kesehatan Mental</h2>
      <p class="muted">Artikel dan panduan praktis yang ditinjau oleh psikolog profesional.</p>
    </div>
    <a href="<?= url('artikel.php') ?>" class="btn btn-outline btn-sm">
      Lihat Semua Artikel <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>

  <div class="grid grid-3">
    <?php foreach ($articles as $art): ?>
      <div class="card" style="display:flex;flex-direction:column;">
        <span class="badge badge-info" style="width:fit-content;margin-bottom:12px;"><?= e($art['kategori']) ?></span>
        <h3 style="font-size:1.15rem;margin-bottom:10px;">
          <a href="<?= url('artikel_detail.php?id=' . $art['id']) ?>"><?= e($art['judul']) ?></a>
        </h3>
        <p class="muted" style="font-size:0.9rem;flex-grow:1;"><?= e($art['ringkasan']) ?></p>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;font-size:0.825rem;color:var(--text-muted);">
          <span><i class="fa-regular fa-clock"></i> <?= format_tanggal($art['created_at']) ?></span>
          <a href="<?= url('artikel_detail.php?id=' . $art['id']) ?>" style="font-weight:600;">Baca Selengkapnya &rarr;</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Call to Action Banner -->
<section class="container" style="margin-top: 50px;">
  <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, #2f695f 100%); color: #fff; padding: 45px 30px; text-align: center; border: none;">
    <h2 style="color: #fff; margin-bottom: 12px; font-size: 2rem;">Siap Mengetahui Kondisi Mentalmu?</h2>
    <p style="max-width: 650px; margin: 0 auto 24px; color: #e2f4f0; font-size: 1.05rem;">
      Pemeriksaan hanya membutuhkan waktu 3-5 menit, gratis, dan kerahasiaan data Anda terlindungi secara penuh.
    </p>
    <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-light btn-lg">
      <i class="fa-solid fa-heart-pulse"></i> Mulai Pemeriksaan Sekarang
    </a>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
