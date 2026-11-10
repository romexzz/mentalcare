<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Laporan & Rekapitulasi Sistem';
$activeMenu = 'laporan';

$tab = $_GET['tab'] ?? 'pemeriksaan';

// Filter tanggal
$tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tglSelesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// 1. Tab Laporan Pemeriksaan
$filterKategori = (int)($_GET['kategori_id'] ?? 0);
$filterRisiko = trim($_GET['tingkat_risiko'] ?? '');

$queryExam = '
    SELECT p.*, u.nama as nama_pasien, u.identitas_nik, u.jenis_kelamin, k.nama as nama_kategori
    FROM pemeriksaan p
    JOIN users u ON u.id = p.pasien_id
    JOIN kategori_assessment k ON k.id = p.kategori_id
    WHERE DATE(p.tanggal_pemeriksaan) BETWEEN ? AND ?
';
$paramsExam = [$tglMulai, $tglSelesai];

if ($filterKategori > 0) {
    $queryExam .= ' AND p.kategori_id = ?';
    $paramsExam[] = $filterKategori;
}
if ($filterRisiko) {
    $queryExam .= ' AND p.tingkat_risiko = ?';
    $paramsExam[] = $filterRisiko;
}
$queryExam .= ' ORDER BY p.tanggal_pemeriksaan DESC';

$stmtExam = db()->prepare($queryExam);
$stmtExam->execute($paramsExam);
$examData = $stmtExam->fetchAll();

// 2. Tab Laporan Konsultasi
$filterPsikolog = (int)($_GET['psikolog_id'] ?? 0);
$filterStatus = trim($_GET['status'] ?? '');

$queryConsult = '
    SELECT c.*, up.nama as nama_pasien, up.email as email_pasien, ups.nama as nama_psikolog
    FROM konsultasi c
    JOIN users up ON up.id = c.pasien_id
    JOIN psikolog p ON p.id = c.psikolog_id
    JOIN users ups ON ups.id = p.user_id
    WHERE c.tanggal BETWEEN ? AND ?
';
$paramsConsult = [$tglMulai, $tglSelesai];

if ($filterPsikolog > 0) {
    $queryConsult .= ' AND c.psikolog_id = ?';
    $paramsConsult[] = $filterPsikolog;
}
if ($filterStatus) {
    $queryConsult .= ' AND c.status = ?';
    $paramsConsult[] = $filterStatus;
}
$queryConsult .= ' ORDER BY c.tanggal DESC';

$stmtConsult = db()->prepare($queryConsult);
$stmtConsult->execute($paramsConsult);
$consultData = $stmtConsult->fetchAll();

// Kategori & Psikolog untuk filter
$categories = db()->query('SELECT id, nama FROM kategori_assessment ORDER BY id')->fetchAll();
$psikologs = db()->query('SELECT p.id, u.nama FROM psikolog p JOIN users u ON u.id = p.user_id ORDER BY u.nama')->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card btn-no-print">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Laporan & Rekapitulasi</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Cetak dan filter rekapitulasi data skrining kesehatan mental dan konsultasi psikolog.
      </p>
    </div>
    <div style="display: flex; gap: 8px;">
      <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-print"></i> Cetak / Ekspor PDF
      </button>
    </div>
  </div>

  <!-- Pilihan Tab Laporan -->
  <div style="display: flex; gap: 10px; margin-bottom: 20px;">
    <a href="<?= url('admin/laporan.php?tab=pemeriksaan&tgl_mulai='.$tglMulai.'&tgl_selesai='.$tglSelesai) ?>" class="btn <?= $tab === 'pemeriksaan' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-clipboard-check"></i> Rekap Pemeriksaan Mental (<?= count($examData) ?>)
    </a>
    <a href="<?= url('admin/laporan.php?tab=konsultasi&tgl_mulai='.$tglMulai.'&tgl_selesai='.$tglSelesai) ?>" class="btn <?= $tab === 'konsultasi' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-comments"></i> Rekap Konsultasi Psikolog (<?= count($consultData) ?>)
    </a>
  </div>

  <!-- Form Filter Rentang Tanggal & Parameter -->
  <form method="get" action="" style="background: var(--bg-body); padding: 18px; border-radius: var(--radius-sm); display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">

    <div>
      <label class="form-label" style="font-size: 0.8rem;">Tanggal Mulai:</label>
      <input type="date" name="tgl_mulai" class="form-control" style="width: 160px;" value="<?= e($tglMulai) ?>">
    </div>

    <div>
      <label class="form-label" style="font-size: 0.8rem;">Tanggal Selesai:</label>
      <input type="date" name="tgl_selesai" class="form-control" style="width: 160px;" value="<?= e($tglSelesai) ?>">
    </div>

    <?php if ($tab === 'pemeriksaan'): ?>
      <div style="min-width: 180px;">
        <label class="form-label" style="font-size: 0.8rem;">Kategori Assessment:</label>
        <select name="kategori_id" class="form-control">
          <option value="0">Semua Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filterKategori === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="min-width: 150px;">
        <label class="form-label" style="font-size: 0.8rem;">Tingkat Risiko:</label>
        <select name="tingkat_risiko" class="form-control">
          <option value="">Semua Risiko</option>
          <option value="Rendah" <?= $filterRisiko === 'Rendah' ? 'selected' : '' ?>>Rendah</option>
          <option value="Sedang" <?= $filterRisiko === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
          <option value="Perlu diperhatikan" <?= $filterRisiko === 'Perlu diperhatikan' ? 'selected' : '' ?>>Perlu diperhatikan</option>
          <option value="Tinggi" <?= $filterRisiko === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
        </select>
      </div>
    <?php else: ?>
      <div style="min-width: 180px;">
        <label class="form-label" style="font-size: 0.8rem;">Psikolog:</label>
        <select name="psikolog_id" class="form-control">
          <option value="0">Semua Psikolog</option>
          <?php foreach ($psikologs as $psi): ?>
            <option value="<?= $psi['id'] ?>" <?= $filterPsikolog === (int)$psi['id'] ? 'selected' : '' ?>>
              <?= e($psi['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="min-width: 150px;">
        <label class="form-label" style="font-size: 0.8rem;">Status Konsultasi:</label>
        <select name="status" class="form-control">
          <option value="">Semua Status</option>
          <option value="menunggu" <?= $filterStatus === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
          <option value="disetujui" <?= $filterStatus === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
          <option value="berlangsung" <?= $filterStatus === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
          <option value="selesai" <?= $filterStatus === 'selesai' ? 'selected' : '' ?>>Selesai</option>
          <option value="dibatalkan" <?= $filterStatus === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
        </select>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-filter"></i> Terapkan Filter
    </button>
  </form>
</div>

<!-- TAMPILAN LAPORAN (SIAP CETAK) -->
<div class="card" style="padding: 30px;">
  <!-- Header Kop Resmi Cetak -->
  <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--primary-dark); padding-bottom: 16px; margin-bottom: 20px;">
    <div>
      <h2 style="margin: 0; color: var(--primary-dark);"><?= APP_NAME ?> - LAYANAN KESEHATAN MENTAL</h2>
      <p class="muted" style="margin: 2px 0 0; font-size: 0.85rem;">
        Laporan Resmi: <?= $tab === 'pemeriksaan' ? 'Rekapitulasi Pemeriksaan Kesehatan Mental' : 'Rekapitulasi Sesi Konsultasi Psikolog' ?>
      </p>
    </div>
    <div style="text-align: right; font-size: 0.825rem; color: var(--text-muted);">
      <div>Periode: <strong><?= format_tanggal($tglMulai) ?> s/d <?= format_tanggal($tglSelesai) ?></strong></div>
      <div>Dicetak Pada: <?= date('d M Y, H:i') ?> WIB</div>
    </div>
  </div>

  <?php if ($tab === 'pemeriksaan'): ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 40px;">No</th>
            <th>Tanggal</th>
            <th>Nama Pasien</th>
            <th>NIK / NIM</th>
            <th>Gender</th>
            <th>Assessment</th>
            <th style="text-align: center;">Skor</th>
            <th>Kategori Hasil</th>
            <th>Tingkat Risiko</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($examData)): ?>
            <?php foreach ($examData as $idx => $ed): ?>
              <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= format_tanggal($ed['tanggal_pemeriksaan']) ?></td>
                <td><strong><?= e($ed['nama_pasien']) ?></strong></td>
                <td><?= e($ed['identitas_nik'] ?: '-') ?></td>
                <td><?= $ed['jenis_kelamin'] === 'L' ? 'L' : ($ed['jenis_kelamin'] === 'P' ? 'P' : '-') ?></td>
                <td><?= e($ed['nama_kategori']) ?></td>
                <td style="text-align: center; font-weight: 700;"><?= (int)$ed['total_skor'] ?></td>
                <td><strong><?= e($ed['kategori_hasil']) ?></strong></td>
                <td><?= badge_risiko($ed['kategori_hasil'], $ed['tingkat_risiko']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9" class="text-center muted" style="padding: 24px;">Tidak ada data pemeriksaan pada periode ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <!-- Tabel Rekap Konsultasi -->
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 40px;">No</th>
            <th>Tanggal</th>
            <th>Pasien</th>
            <th>Psikolog</th>
            <th>Metode</th>
            <th>Keluhan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($consultData)): ?>
            <?php foreach ($consultData as $idx => $cd): ?>
              <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= format_tanggal($cd['tanggal']) ?> <small class="muted">(<?= substr($cd['waktu'], 0, 5) ?>)</small></td>
                <td><strong><?= e($cd['nama_pasien']) ?></strong></td>
                <td><?= e($cd['nama_psikolog']) ?></td>
                <td><?= e($cd['metode']) ?></td>
                <td style="max-width: 250px; font-size: 0.85rem;"><?= e(mb_strimwidth($cd['keluhan'], 0, 70, '...')) ?></td>
                <td><?= badge_status($cd['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" class="text-center muted" style="padding: 24px;">Tidak ada data konsultasi pada periode ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Lembar Tanda Tangan Cetak -->
  <div style="margin-top: 40px; display: flex; justify-content: flex-end; text-align: center;">
    <div style="width: 220px;">
      <p style="font-size: 0.85rem; margin-bottom: 60px;">
        Jakarta, <?= format_tanggal(date('Y-m-d')) ?><br>
        <strong>Administrator Sistem,</strong>
      </p>
      <p style="font-size: 0.9rem; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin: 0;">
        <?= e($u['nama']) ?>
      </p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
