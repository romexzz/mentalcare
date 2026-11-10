<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$pasienId = (int)($_GET['id'] ?? 0);

$stmtPsi = db()->prepare('SELECT id FROM psikolog WHERE user_id = ?');
$stmtPsi->execute([$u['id']]);
$psiId = (int)$stmtPsi->fetchColumn();

// 1. Verifikasi Hubungan Pasien dengan Psikolog ini
$stmtCek = db()->prepare('
    SELECT c.*, u.nama, u.email, u.no_hp, u.jenis_kelamin, u.tanggal_lahir, u.identitas_nik
    FROM konsultasi c
    JOIN users u ON u.id = c.pasien_id
    WHERE c.pasien_id = ? AND c.psikolog_id = ?
    ORDER BY c.tanggal DESC
');
$stmtCek->execute([$pasienId, $psiId]);
$consultations = $stmtCek->fetchAll();

if (empty($consultations)) {
    flash('Akses ditolak. Anda tidak memiliki hubungan konsultasi dengan pasien ini.', 'danger');
    redirect('psikolog/pasien.php');
}

$patientInfo = $consultations[0];

// Cek apakah ada izin akses riwayat pemeriksaan dari pasien
$hasPermission = false;
foreach ($consultations as $c) {
    if ((int)$c['izinkan_akses_riwayat'] === 1) {
        $hasPermission = true;
        break;
    }
}

// 2. Jika ada izin, ambil data riwayat pemeriksaan pasien
$screenings = [];
if ($hasPermission) {
    $stmtScr = db()->prepare('
        SELECT p.*, k.nama as nama_kategori, k.icon
        FROM pemeriksaan p
        JOIN kategori_assessment k ON k.id = p.kategori_id
        WHERE p.pasien_id = ?
        ORDER BY p.tanggal_pemeriksaan DESC
    ');
    $stmtScr->execute([$pasienId]);
    $screenings = $stmtScr->fetchAll();
}

$pageTitle = 'Rekam Pasien: ' . $patientInfo['nama'];
$activeMenu = 'pasien';
require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<div style="margin-bottom: 20px;">
  <a href="<?= url('psikolog/pasien.php') ?>" class="btn btn-light btn-sm">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Pasien
  </a>
</div>

<!-- Kartu Informasi Pasien -->
<div class="card" style="padding: 28px; margin-bottom: 24px;">
  <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
    <div style="width: 70px; height: 70px; border-radius: var(--radius-full); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem;">
      <i class="fa-solid fa-user"></i>
    </div>
    <div style="flex-grow: 1;">
      <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <h2 style="margin: 0; font-size: 1.5rem; color: var(--primary-dark);"><?= e($patientInfo['nama']) ?></h2>
        <?php if ($hasPermission): ?>
          <span class="badge badge-success"><i class="fa-solid fa-lock-open"></i> Izin Akses Riwayat Aktif</span>
        <?php else: ?>
          <span class="badge badge-warning"><i class="fa-solid fa-lock"></i> Tanpa Izin Akses Riwayat</span>
        <?php endif; ?>
      </div>
      <div style="display: flex; gap: 18px; color: var(--text-muted); font-size: 0.875rem; margin-top: 8px; flex-wrap: wrap;">
        <span><i class="fa-solid fa-envelope"></i> <?= e($patientInfo['email']) ?></span>
        <span><i class="fa-solid fa-phone"></i> <?= e($patientInfo['no_hp'] ?: '-') ?></span>
        <span><i class="fa-solid fa-venus-mars"></i> <?= $patientInfo['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($patientInfo['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></span>
        <span><i class="fa-solid fa-cake-candles"></i> <?= format_tanggal($patientInfo['tanggal_lahir']) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Bagian Riwayat Pemeriksaan Pasien (Sesuai Hak Akses) -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <div class="card-title">
      <i class="fa-solid fa-file-waveform text-primary"></i> Riwayat Skrining Mandiri Pasien
    </div>
  </div>

  <?php if ($hasPermission): ?>
    <?php if (!empty($screenings)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal Tes</th>
              <th>Jenis Assessment</th>
              <th>Total Skor</th>
              <th>Kategori Hasil</th>
              <th>Tingkat Risiko</th>
              <th>Rekomendasi Awal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($screenings as $s): ?>
              <tr>
                <td><?= format_tanggal($s['tanggal_pemeriksaan'], true) ?></td>
                <td>
                  <span class="badge badge-light" style="font-size: 0.85rem;">
                    <i class="fa-solid <?= e($s['icon'] ?: 'fa-brain') ?>"></i> <?= e($s['nama_kategori']) ?>
                  </span>
                </td>
                <td><span style="font-weight: 800; font-size: 1.1rem; color: var(--primary-dark);"><?= (int)$s['total_skor'] ?></span></td>
                <td><strong><?= e($s['kategori_hasil']) ?></strong></td>
                <td><?= badge_risiko($s['kategori_hasil'], $s['tingkat_risiko']) ?></td>
                <td style="max-width: 320px; font-size: 0.85rem; color: var(--text-main);">
                  <?= nl2br(e($s['rekomendasi'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 24px 0;">Pasien belum pernah melakukan pemeriksaan mandiri.</p>
    <?php endif; ?>
  <?php else: ?>
    <!-- Peringatan Pembatasan Akses Data Sensitif Pasien -->
    <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: var(--radius-sm); padding: 22px; text-align: center; color: #92400e;">
      <i class="fa-solid fa-user-shield" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
      <h4 style="color: #92400e; margin-bottom: 6px;">Data Sensitif Terlindungi</h4>
      <p style="font-size: 0.9rem; margin: 0; max-width: 600px; margin: 0 auto;">
        Pasien memilih untuk tidak membagikan riwayat hasil kuisioner mandirinya. Sesuai standar privasi kode etik psikologi dan keamanan sistem, data skrining pasien tidak dapat ditampilkan kepada psikolog tanpa izin eksplisit dari pasien.
      </p>
    </div>
  <?php endif; ?>
</div>

<!-- Sesi Konsultasi dengan Pasien ini -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <i class="fa-solid fa-comments text-secondary"></i> Riwayat Sesi Konsultasi Bersama Anda
    </div>
  </div>

  <div style="display: flex; flex-direction: column; gap: 16px;">
    <?php foreach ($consultations as $cs): ?>
      <?php 
        $stmtNote = db()->prepare('SELECT * FROM catatan_konsultasi WHERE konsultasi_id = ?');
        $stmtNote->execute([$cs['id']]);
        $note = $stmtNote->fetch();
      ?>
      <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
          <div>
            <strong style="font-size: 1rem; color: var(--primary-dark);">
              <i class="fa-regular fa-calendar text-primary"></i> <?= format_tanggal($cs['tanggal']) ?> (<?= substr($cs['waktu'], 0, 5) ?> WIB)
            </strong>
            <span class="muted" style="font-size: 0.85rem; margin-left: 10px;">Metode: <?= e($cs['metode']) ?></span>
          </div>
          <div>
            <?= badge_status($cs['status']) ?>
            <a href="<?= url('psikolog/catatan.php?konsultasi_id=' . $cs['id']) ?>" class="btn btn-light btn-sm" style="margin-left: 8px;">
              <i class="fa-solid fa-pen-to-square"></i> <?= $note ? 'Edit Catatan' : 'Tulis Catatan' ?>
            </a>
          </div>
        </div>

        <div style="font-size: 0.875rem; margin-bottom: 10px;">
          <strong>Keluhan Pasien:</strong> <?= e($cs['keluhan']) ?>
        </div>

        <?php if ($note): ?>
          <div style="background: var(--bg-body); padding: 14px; border-radius: var(--radius-sm); border-left: 3px solid var(--secondary); font-size: 0.875rem;">
            <div><strong>Hasil Konsultasi:</strong> <?= nl2br(e($note['hasil_konsultasi'])) ?></div>
            <div style="margin-top: 6px;"><strong>Rekomendasi:</strong> <?= nl2br(e($note['rekomendasi'])) ?></div>
            <div style="margin-top: 6px;"><strong>Tindak Lanjut:</strong> <?= nl2br(e($note['tindak_lanjut'])) ?></div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
