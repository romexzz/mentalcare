<?php
require_once __DIR__ . '/../config/config.php';
?>

<!-- Tombol Darurat Melayang (Hotline Kemenkes RI) -->
<a href="tel:119" class="emergency-pill" title="Layanan Dukungan Krisis Kesehatan Mental">
  <i class="fa-solid fa-phone-volume"></i>
  <span>Darurat: Hubungi 119 ext 8</span>
</a>

<!-- Footer Publik -->
<footer class="public-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <div class="brand-logo" style="margin-bottom: 14px;">
          <i class="fa-solid fa-leaf"></i>
          <span><?= APP_NAME ?></span>
        </div>
        <p class="muted">
          Platform layanan kesehatan mental yang memberikan ruang aman untuk pemeriksaan mandiri, edukasi berbasis bukti ilmiah, dan konsultasi profesional bersama psikolog terpercaya.
        </p>
        <p class="muted" style="font-size: 0.85rem; margin-top: 10px;">
          <i class="fa-solid fa-shield-heart text-primary"></i> Privasi data dan kerahasiaan Anda terlindungi.
        </p>
      </div>

      <div class="footer-col">
        <h4>Menu Utama</h4>
        <ul>
          <li><a href="<?= url('index.php') ?>">Beranda</a></li>
          <li><a href="<?= url('tentang.php') ?>">Tentang Kami</a></li>
          <li><a href="<?= url('layanan.php') ?>">Layanan & Fasilitas</a></li>
          <li><a href="<?= url('artikel.php') ?>">Artikel & Edukasi</a></li>
          <li><a href="<?= url('kontak.php') ?>">Kontak Dukungan</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Fitur Layanan</h4>
        <ul>
          <li><a href="<?= url('pasien/assessment.php') ?>">Pemeriksaan Mental</a></li>
          <li><a href="<?= url('pasien/psikolog.php') ?>">Konsultasi Psikolog</a></li>
          <li><a href="<?= url('pasien/riwayat.php') ?>">Riwayat Pemeriksaan</a></li>
          <li><a href="<?= url('login.php') ?>">Portal Masuk</a></li>
          <li><a href="<?= url('register.php') ?>">Daftar Pasien</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Bantuan & Krisis</h4>
        <p class="muted" style="font-size: 0.9rem;">
          Jika Anda atau orang terdekat berada dalam krisis atau membutuhkan bantuan segera:
        </p>
        <div style="background: var(--primary-light); padding: 12px; border-radius: var(--radius-sm); margin-top: 10px;">
          <strong>Hotline SEJIWA:</strong><br>
          <a href="tel:119" style="font-size: 1.1rem; font-weight: 700; color: var(--primary-dark);">
            <i class="fa-solid fa-phone"></i> 119 Ext 8
          </a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Hak Cipta Dilindungi Undang-Undang. Disclaimer: Skrining mandiri bukan pengganti diagnosis medis resmi.</p>
    </div>
  </div>
</footer>

<!-- App JS -->
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
