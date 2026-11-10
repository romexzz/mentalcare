<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Pilih Pemeriksaan Kesehatan Mental';
$activeMenu = 'assessment';

// Ambil semua kategori assessment aktif beserta jumlah pertanyaannya
$stmt = db()->query('
    SELECT k.*, COUNT(p.id) as total_pertanyaan
    FROM kategori_assessment k
    LEFT JOIN pertanyaan p ON p.kategori_id = k.id AND p.status = "aktif"
    WHERE k.status = "aktif"
    GROUP BY k.id
    ORDER BY k.id ASC
');
$categories = $stmt->fetchAll();

require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="assessment-container">
  <div style="margin-bottom: 24px;">
    <h2>Pilih Kategori Pemeriksaan</h2>
    <p class="muted">
      Silakan pilih instrumen skrining yang sesuai dengan kondisi atau keluhan yang ingin Anda periksa saat ini.
    </p>
  </div>

  <div class="grid grid-2">
    <?php foreach ($categories as $cat): ?>
      <div class="card feature-card" style="margin-bottom: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
          <div class="feature-icon-wrapper" style="margin-bottom: 0;">
            <i class="fa-solid <?= e($cat['icon'] ?: 'fa-brain') ?>"></i>
          </div>
          <span class="badge badge-info">
            <i class="fa-solid fa-list-check"></i> <?= (int)$cat['total_pertanyaan'] ?> Pertanyaan
          </span>
        </div>

        <h3 style="font-size: 1.25rem; margin-bottom: 8px;"><?= e($cat['nama']) ?></h3>
        <p class="muted" style="font-size: 0.925rem; flex-grow: 1;"><?= e($cat['deskripsi']) ?></p>

        <?php if (!empty($cat['instruksi'])): ?>
          <div style="background: var(--bg-body); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.825rem; color: var(--text-muted); margin-bottom: 18px;">
            <i class="fa-solid fa-circle-info text-primary"></i> <?= e($cat['instruksi']) ?>
          </div>
        <?php endif; ?>

        <div>
          <?php if ((int)$cat['total_pertanyaan'] > 0): ?>
            <a href="<?= url('pasien/kuisioner.php?id=' . $cat['id']) ?>" class="btn btn-primary btn-block">
              <i class="fa-solid fa-pen-to-square"></i> Mulai Kuisioner
            </a>
          <?php else: ?>
            <button class="btn btn-light btn-block" disabled>Belum Ada Pertanyaan</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="disclaimer-card" style="margin-top: 30px;">
    <i class="fa-solid fa-shield-halved"></i>
    <div>
      <strong>Perhatian Medis:</strong> Seluruh kuisioner di MentalCare adalah alat skrining mandiri awal untuk membantu Anda mengenali pola emosi. Hasil skrining bukan merupakan diagnosis klinis resmi. Jika Anda merasa membutuhkan diagnosis medis atau resep obat, mohon konsultasikan langsung dengan psikolog berlisensi atau psikiater.
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
