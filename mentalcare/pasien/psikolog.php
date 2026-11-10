<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Daftar Psikolog Profesional';
$activeMenu = 'psikolog';

// Ambil semua psikolog terverifikasi dan akunnya aktif
$stmt = db()->query('
    SELECT p.*, u.nama, u.email, u.no_hp, u.foto
    FROM psikolog p
    JOIN users u ON u.id = p.user_id
    WHERE p.status_verifikasi = "terverifikasi" AND u.status = "aktif"
    ORDER BY p.id ASC
');
$psikologs = $stmt->fetchAll();

// Ambil jadwal ketersediaan mendatang untuk masing-masing psikolog
$stmtJadwal = db()->query('
    SELECT * FROM jadwal_psikolog 
    WHERE tanggal >= CURDATE() AND status = "tersedia"
    ORDER BY tanggal ASC, jam_mulai ASC
');
$allSchedules = $stmtJadwal->fetchAll();

$schedulesByPsikolog = [];
foreach ($allSchedules as $sch) {
    $schedulesByPsikolog[$sch['psikolog_id']][] = $sch;
}

require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div style="margin-bottom: 24px;">
  <h2>Daftar Psikolog Berlisensi</h2>
  <p class="muted">
    Pilih psikolog profesional sesuai dengan kebutuhan dan spesialisasi keluhan Anda untuk memulai sesi konsultasi privat.
  </p>
</div>

<div class="grid grid-2">
  <?php foreach ($psikologs as $psi): ?>
    <?php $schedules = $schedulesByPsikolog[$psi['id']] ?? []; ?>
    <div class="card" style="display: flex; flex-direction: column; padding: 28px;">
      <div style="display: flex; gap: 18px; margin-bottom: 16px; align-items: flex-start;">
        <div style="width: 70px; height: 70px; border-radius: var(--radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0;">
          <i class="fa-solid fa-user-doctor"></i>
        </div>
        <div>
          <span class="badge badge-success" style="font-size: 0.75rem; margin-bottom: 6px;">
            <i class="fa-solid fa-certificate"></i> <?= e($psi['nomor_izin'] ?: 'Psikolog Terverifikasi') ?>
          </span>
          <h3 style="font-size: 1.25rem; margin-bottom: 4px; color: var(--primary-dark);"><?= e($psi['nama']) ?></h3>
          <p class="muted" style="font-size: 0.85rem; margin: 0;">
            <i class="fa-solid fa-graduation-cap text-primary"></i> <?= e($psi['pendidikan']) ?>
          </p>
        </div>
      </div>

      <div style="background: var(--bg-body); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
        <div style="font-size: 0.85rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 4px;">
          <i class="fa-solid fa-stethoscope text-primary"></i> Spesialisasi & Pengalaman:
        </div>
        <div style="font-size: 0.9rem; color: var(--text-main);">
          <?= e($psi['spesialisasi']) ?> &bull; <strong>Pengalaman: <?= e($psi['pengalaman']) ?></strong>
        </div>
      </div>

      <?php if (!empty($psi['deskripsi'])): ?>
        <p class="muted" style="font-size: 0.9rem; flex-grow: 1; margin-bottom: 18px;">
          <?= e($psi['deskripsi']) ?>
        </p>
      <?php endif; ?>

      <!-- Jadwal Ketersediaan -->
      <div style="border-top: 1px solid var(--border-light); padding-top: 14px; margin-top: auto;">
        <div style="font-size: 0.825rem; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
          <i class="fa-regular fa-calendar-check text-success"></i> Slot Jadwal Mendatang:
        </div>
        <?php if (!empty($schedules)): ?>
          <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px;">
            <?php foreach (array_slice($schedules, 0, 4) as $sc): ?>
              <span class="badge badge-secondary" style="font-size: 0.75rem;">
                <?= date('d M', strtotime($sc['tanggal'])) ?> (<?= substr($sc['jam_mulai'], 0, 5) ?>)
              </span>
            <?php endforeach; ?>
            <?php if (count($schedules) > 4): ?>
              <span class="badge badge-light" style="font-size: 0.75rem;">+<?= count($schedules) - 4 ?> lainnya</span>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p class="muted" style="font-size: 0.8rem; margin-bottom: 16px;">
            <em>Jadwal slot fleksibel (Anda dapat mengajukan tanggal sendiri pada form pengajuan).</em>
          </p>
        <?php endif; ?>

        <a href="<?= url('pasien/konsultasi_booking.php?psikolog_id=' . $psi['id']) ?>" class="btn btn-primary btn-block">
          <i class="fa-solid fa-calendar-plus"></i> Ajukan Konsultasi Sekarang
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
