<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$pageTitle = 'Dashboard Psikolog';
$activeMenu = 'dashboard';

// Ambil entri psikolog berdasarkan user_id
$stmtPsi = db()->prepare('SELECT * FROM psikolog WHERE user_id = ?');
$stmtPsi->execute([$u['id']]);
$psi = $stmtPsi->fetch();

if (!$psi) {
    // Jika belum ada baris psikolog, buat entri dasar
    db()->prepare('
        INSERT INTO psikolog (user_id, nomor_izin, spesialisasi, pendidikan, pengalaman, deskripsi, status_verifikasi)
        VALUES (?, "SIPP-MENUNGGU", "Psikologi Klinis", "Magister Psikologi Profesi", "3 Tahun", "Psikolog Mitra MentalCare", "terverifikasi")
    ')->execute([$u['id']]);
    $psiId = db()->lastInsertId();
    $psi = ['id' => $psiId];
} else {
    $psiId = $psi['id'];
}

// 1. Statistik Dashboard Psikolog:
// - Jumlah Pasien Unik
$stmtCountPasien = db()->prepare('SELECT COUNT(DISTINCT pasien_id) FROM konsultasi WHERE psikolog_id = ?');
$stmtCountPasien->execute([$psiId]);
$totalPasien = (int)$stmtCountPasien->fetchColumn();

// - Total Konsultasi
$stmtTotalKonsul = db()->prepare('SELECT COUNT(*) FROM konsultasi WHERE psikolog_id = ?');
$stmtTotalKonsul->execute([$psiId]);
$totalKonsultasi = (int)$stmtTotalKonsul->fetchColumn();

// - Konsultasi Menunggu Konfirmasi
$stmtMenunggu = db()->prepare('SELECT COUNT(*) FROM konsultasi WHERE psikolog_id = ? AND status = "menunggu"');
$stmtMenunggu->execute([$psiId]);
$totalMenunggu = (int)$stmtMenunggu->fetchColumn();

// - Konsultasi Hari Ini
$stmtHariIni = db()->prepare('SELECT COUNT(*) FROM konsultasi WHERE psikolog_id = ? AND tanggal = CURDATE() AND status != "dibatalkan"');
$stmtHariIni->execute([$psiId]);
$totalHariIni = (int)$stmtHariIni->fetchColumn();

// 2. Konsultasi Hari Ini & Mendatang (Butuh Perhatian)
$stmtUpcoming = db()->prepare('
    SELECT c.*, u.nama as nama_pasien, u.no_hp, u.email, u.jenis_kelamin, u.tanggal_lahir
    FROM konsultasi c
    JOIN users u ON u.id = c.pasien_id
    WHERE c.psikolog_id = ? AND c.tanggal >= CURDATE() AND c.status IN ("menunggu", "disetujui", "berlangsung")
    ORDER BY c.tanggal ASC, c.waktu ASC
    LIMIT 5
');
$stmtUpcoming->execute([$psiId]);
$upcomingConsults = $stmtUpcoming->fetchAll();

// 3. Daftar Pasien Terbaru
$stmtRecentPasien = db()->prepare('
    SELECT DISTINCT u.id as pasien_id, u.nama, u.email, u.no_hp, u.jenis_kelamin, MAX(c.tanggal) as konsultasi_terakhir, c.izinkan_akses_riwayat
    FROM konsultasi c
    JOIN users u ON u.id = c.pasien_id
    WHERE c.psikolog_id = ?
    GROUP BY u.id, u.nama, u.email, u.no_hp, u.jenis_kelamin, c.izinkan_akses_riwayat
    ORDER BY konsultasi_terakhir DESC
    LIMIT 5
');
$stmtRecentPasien->execute([$psiId]);
$recentPatients = $stmtRecentPasien->fetchAll();

require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<!-- Sapaan Psikolog -->
<div class="card" style="background: linear-gradient(135deg, #e0f2fe 0%, #ffffff 100%); border-left: 5px solid var(--secondary); padding: 26px; margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div>
      <h2 style="margin-bottom: 4px;">Selamat Bertugas, <?= e($u['nama']) ?></h2>
      <p class="muted" style="margin: 0; font-size: 0.95rem;">
        Berikut adalah ikhtisar sesi praktik, permintaan konsultasi pasien, dan agenda konseling hari ini.
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="<?= url('psikolog/jadwal.php') ?>" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-calendar-days"></i> Kelola Slot Jadwal
      </a>
      <a href="<?= url('psikolog/konsultasi.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-list-check"></i> Kelola Konsultasi
      </a>
    </div>
  </div>
</div>

<!-- 4 Kartu Statistik -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat-card">
    <div class="stat-icon blue">
      <i class="fa-solid fa-users"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalPasien ?></h3>
      <p>Jumlah Pasien</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <i class="fa-solid fa-calendar-check"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalKonsultasi ?></h3>
      <p>Total Konsultasi</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber">
      <i class="fa-solid fa-clock"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalMenunggu ?></h3>
      <p>Menunggu Konfirmasi</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">
      <i class="fa-solid fa-bell"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalHariIni ?></h3>
      <p>Konsultasi Hari Ini</p>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <!-- Sesi Konsultasi Terdekat & Permintaan Baru -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-calendar-days text-secondary"></i> Konsultasi Terdekat & Menunggu
      </div>
      <a href="<?= url('psikolog/konsultasi.php') ?>" class="btn btn-light btn-sm">Semua Sesi</a>
    </div>

    <?php if (!empty($upcomingConsults)): ?>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($upcomingConsults as $uc): ?>
          <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; background: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <strong style="color: var(--primary-dark); font-size: 1rem;">
                <i class="fa-solid fa-user"></i> <?= e($uc['nama_pasien']) ?>
              </strong>
              <?= badge_status($uc['status']) ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">
              <span><i class="fa-regular fa-calendar text-primary"></i> <?= format_tanggal($uc['tanggal']) ?></span> &bull; 
              <span><i class="fa-regular fa-clock text-primary"></i> <?= substr($uc['waktu'], 0, 5) ?> WIB</span> &bull;
              <span><i class="fa-solid fa-video text-primary"></i> <?= e($uc['metode']) ?></span>
            </div>
            <p class="muted" style="font-size: 0.85rem; margin-bottom: 10px;">
              <strong>Keluhan:</strong> <?= e(mb_strimwidth($uc['keluhan'], 0, 80, '...')) ?>
            </p>
            <div style="display: flex; gap: 8px;">
              <a href="<?= url('psikolog/konsultasi.php?id=' . $uc['id']) ?>" class="btn btn-light btn-sm">
                <i class="fa-solid fa-sliders"></i> Proses Sesi
              </a>
              <?php if ($uc['izinkan_akses_riwayat']): ?>
                <a href="<?= url('psikolog/pasien_detail.php?id=' . $uc['pasien_id']) ?>" class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-file-waveform"></i> Riwayat Pasien
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 24px 0;">Tidak ada jadwal konsultasi mendatang saat ini.</p>
    <?php endif; ?>
  </div>

  <!-- Daftar Pasien Terbaru -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-users text-primary"></i> Pasien Terbaru
      </div>
      <a href="<?= url('psikolog/pasien.php') ?>" class="btn btn-light btn-sm">Lihat Semua</a>
    </div>

    <?php if (!empty($recentPatients)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Nama Pasien</th>
              <th>Gender</th>
              <th>Konsultasi Terakhir</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentPatients as $rp): ?>
              <tr>
                <td>
                  <strong><?= e($rp['nama']) ?></strong><br>
                  <span class="muted" style="font-size: 0.75rem;"><?= e($rp['email']) ?></span>
                </td>
                <td><?= $rp['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($rp['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></td>
                <td><?= format_tanggal($rp['konsultasi_terakhir']) ?></td>
                <td>
                  <a href="<?= url('psikolog/pasien_detail.php?id=' . $rp['pasien_id']) ?>" class="btn btn-light btn-sm" title="Lihat Rekam Pasien">
                    <i class="fa-solid fa-folder-open"></i> Detail
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 24px 0;">Belum ada riwayat pasien yang tercatat.</p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
