<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Dashboard Pasien';
$activeMenu = 'dashboard';

// 1. Ambil Pemeriksaan Terakhir Pasien
$stmtLast = db()->prepare('
    SELECT p.*, k.nama as nama_kategori, k.icon 
    FROM pemeriksaan p
    JOIN kategori_assessment k ON k.id = p.kategori_id
    WHERE p.pasien_id = ?
    ORDER BY p.tanggal_pemeriksaan DESC
    LIMIT 1
');
$stmtLast->execute([$u['id']]);
$lastCheck = $stmtLast->fetch();

// 2. Ambil 5 Riwayat Pemeriksaan Terkini
$stmtHistory = db()->prepare('
    SELECT p.*, k.nama as nama_kategori 
    FROM pemeriksaan p
    JOIN kategori_assessment k ON k.id = p.kategori_id
    WHERE p.pasien_id = ?
    ORDER BY p.tanggal_pemeriksaan DESC
    LIMIT 5
');
$stmtHistory->execute([$u['id']]);
$history = $stmtHistory->fetchAll();

// 3. Ambil Jadwal Konsultasi Mendatang / Aktif
$stmtConsult = db()->prepare('
    SELECT c.*, u.nama as nama_psikolog, p.spesialisasi, p.nomor_izin
    FROM konsultasi c
    JOIN psikolog p ON p.id = c.psikolog_id
    JOIN users u ON u.id = p.user_id
    WHERE c.pasien_id = ? AND c.status IN ("menunggu", "disetujui", "berlangsung")
    ORDER BY c.tanggal ASC, c.waktu ASC
    LIMIT 3
');
$stmtConsult->execute([$u['id']]);
$upcomingConsults = $stmtConsult->fetchAll();

require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<!-- Banner Sapaan Ramah -->
<div class="card" style="background: linear-gradient(135deg, #eef8f6 0%, #ffffff 100%); border-left: 5px solid var(--primary); padding: 28px; margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h2 style="margin-bottom: 6px;">Halo, <?= e($u['nama']) ?>! 👋</h2>
      <p class="muted" style="margin: 0; font-size: 1rem;">
        Bagaimana perasaanmu hari ini? Ambil jeda sejenak dan dengarkan apa yang tubuh dan pikiranmu rasakan.
      </p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-primary">
        <i class="fa-solid fa-clipboard-check"></i> Mulai Pemeriksaan
      </a>
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-user-doctor"></i> Konsultasi dengan Psikolog
      </a>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <!-- Kolom Kiri: Status Kesehatan Mental Terakhir & Rekomendasi -->
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-solid fa-chart-simple text-primary"></i> Status Kesehatan Mental Terakhir
        </div>
        <?php if ($lastCheck): ?>
          <a href="<?= url('pasien/hasil.php?id=' . $lastCheck['id']) ?>" class="btn btn-outline btn-sm">
            Lihat Detail Hasil
          </a>
        <?php endif; ?>
      </div>

      <?php if ($lastCheck): ?>
        <div style="display: flex; align-items: center; gap: 20px; padding: 10px 0 20px; border-bottom: 1px solid var(--border-light); flex-wrap: wrap;">
          <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-light); display: flex; flex-direction: column; align-items: center; justify-content: center; border: 3px solid var(--primary);">
            <span style="font-size: 1.75rem; font-weight: 800; color: var(--primary-dark); line-height: 1;"><?= (int)$lastCheck['total_skor'] ?></span>
            <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">SKOR</span>
          </div>

          <div style="flex-grow: 1;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
              Pemeriksaan: <?= e($lastCheck['nama_kategori']) ?>
            </div>
            <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary-dark); margin: 2px 0;">
              Kategori: <?= e($lastCheck['kategori_hasil']) ?>
            </div>
            <div>
              Tingkat Risiko: <?= badge_risiko($lastCheck['kategori_hasil'], $lastCheck['tingkat_risiko']) ?>
              <span class="muted" style="font-size: 0.825rem; margin-left: 8px;">
                <i class="fa-regular fa-clock"></i> <?= format_tanggal($lastCheck['tanggal_pemeriksaan']) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Rekomendasi Terakhir -->
        <div style="margin-top: 18px;">
          <h4 style="font-size: 1rem; margin-bottom: 8px; color: var(--primary-dark);">
            <i class="fa-solid fa-lightbulb text-warning"></i> Rekomendasi Untukmu:
          </h4>
          <p style="font-size: 0.925rem; color: var(--text-main); background: #fbfdfc; padding: 14px; border-radius: var(--radius-sm); border-left: 3px solid var(--primary);">
            <?= nl2br(e($lastCheck['rekomendasi'])) ?>
          </p>
        </div>
      <?php else: ?>
        <div class="text-center" style="padding: 30px 10px;">
          <div style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;">
            <i class="fa-regular fa-clipboard"></i>
          </div>
          <h4>Belum Ada Pemeriksaan</h4>
          <p class="muted" style="font-size: 0.9rem; max-width: 320px; margin: 0 auto 18px;">
            Anda belum pernah mengisi kuisioner kesehatan mental. Yuk, ketahui kondisi Anda sekarang!
          </p>
          <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-play"></i> Mulai Pemeriksaan Pertama
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Kolom Kanan: Jadwal Konsultasi Anda -->
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-regular fa-calendar-check text-secondary"></i> Jadwal Konsultasi Anda
        </div>
        <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-light btn-sm">
          <i class="fa-solid fa-plus"></i> Booking Baru
        </a>
      </div>

      <?php if (!empty($upcomingConsults)): ?>
        <?php foreach ($upcomingConsults as $c): ?>
          <div style="padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 12px; background: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
              <strong style="color: var(--primary-dark); font-size: 1rem;">
                <i class="fa-solid fa-user-doctor text-secondary"></i> <?= e($c['nama_psikolog']) ?>
              </strong>
              <?= badge_status($c['status']) ?>
            </div>
            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 6px;">
              <span><i class="fa-regular fa-calendar"></i> <?= format_tanggal($c['tanggal']) ?></span> &bull; 
              <span><i class="fa-regular fa-clock"></i> <?= substr($c['waktu'], 0, 5) ?> WIB</span> &bull;
              <span><i class="fa-solid fa-video"></i> <?= e($c['metode']) ?></span>
            </div>
            <div class="muted" style="font-size: 0.85rem; margin-bottom: 8px;">
              Keluhan: <?= e(mb_strimwidth($c['keluhan'], 0, 90, '...')) ?>
            </div>
            <?php if ($c['status'] === 'disetujui' && !empty($c['link_konsultasi'])): ?>
              <a href="<?= e($c['link_konsultasi']) ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-video"></i> Masuk Ruang Konsultasi
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div style="text-align: right; margin-top: 10px;">
          <a href="<?= url('pasien/konsultasi_riwayat.php') ?>" style="font-size: 0.85rem; font-weight: 600;">Lihat Semua Konsultasi &rarr;</a>
        </div>
      <?php else: ?>
        <div class="text-center" style="padding: 30px 10px;">
          <div style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;">
            <i class="fa-regular fa-calendar-xmark"></i>
          </div>
          <h4>Belum Ada Jadwal Konsultasi</h4>
          <p class="muted" style="font-size: 0.9rem; margin-bottom: 16px;">
            Butuh teman bercerita atau bimbingan profesional? Psikolog kami siap membantu.
          </p>
          <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-user-doctor"></i> Temukan Psikolog
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Riwayat Pemeriksaan Terbaru (Tabel) -->
<div class="card" style="margin-top: 24px;">
  <div class="card-header">
    <div class="card-title">
      <i class="fa-solid fa-clock-rotate-left text-primary"></i> Riwayat Pemeriksaan Terkini
    </div>
    <a href="<?= url('pasien/riwayat.php') ?>" class="btn btn-light btn-sm">
      Lihat Semua Riwayat
    </a>
  </div>

  <?php if (!empty($history)): ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Jenis Pemeriksaan</th>
            <th>Total Skor</th>
            <th>Kategori Hasil</th>
            <th>Tingkat Risiko</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= format_tanggal($h['tanggal_pemeriksaan'], true) ?></td>
              <td><strong><?= e($h['nama_kategori']) ?></strong></td>
              <td><span style="font-weight: 700; font-size: 1.05rem;"><?= (int)$h['total_skor'] ?></span></td>
              <td><strong><?= e($h['kategori_hasil']) ?></strong></td>
              <td><?= badge_risiko($h['kategori_hasil'], $h['tingkat_risiko']) ?></td>
              <td>
                <a href="<?= url('pasien/hasil.php?id=' . $h['id']) ?>" class="btn btn-light btn-sm">
                  <i class="fa-solid fa-eye"></i> Hasil & Rekomendasi
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted text-center" style="padding: 20px 0;">Belum ada riwayat pemeriksaan yang tercatat.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
