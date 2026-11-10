<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Riwayat Pemeriksaan Kesehatan Mental';
$activeMenu = 'riwayat';

// Filter
$filterKat = (int)($_GET['kategori_id'] ?? 0);
$query = '
    SELECT p.*, k.nama as nama_kategori, k.icon
    FROM pemeriksaan p
    JOIN kategori_assessment k ON k.id = p.kategori_id
    WHERE p.pasien_id = ?
';
$params = [$u['id']];

if ($filterKat > 0) {
    $query .= ' AND p.kategori_id = ?';
    $params[] = $filterKat;
}
$query .= ' ORDER BY p.tanggal_pemeriksaan DESC';

$stmt = db()->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll();

// Kategori untuk dropdown filter
$kategoriList = db()->query('SELECT id, nama FROM kategori_assessment ORDER BY id')->fetchAll();

require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Riwayat Pemeriksaan Anda</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Catatan perkembangan skor dan hasil skrining kesehatan mental Anda dari waktu ke waktu.
      </p>
    </div>
    <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Pemeriksaan Baru
    </a>
  </div>

  <!-- Filter Form -->
  <form method="get" action="" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: flex-end;">
    <div style="min-width: 200px;">
      <label class="form-label" style="font-size: 0.825rem;">Filter Kategori:</label>
      <select name="kategori_id" class="form-control" onchange="this.form.submit()">
        <option value="0">Semua Kategori</option>
        <?php foreach ($kategoriList as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $filterKat === (int)$k['id'] ? 'selected' : '' ?>>
            <?= e($k['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($filterKat > 0): ?>
      <a href="<?= url('pasien/riwayat.php') ?>" class="btn btn-light btn-sm" style="margin-bottom: 2px;">
        <i class="fa-solid fa-rotate-left"></i> Reset Filter
      </a>
    <?php endif; ?>
  </form>

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
            <th>Rekomendasi Singkat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= format_tanggal($h['tanggal_pemeriksaan'], true) ?></td>
              <td>
                <span class="badge badge-light" style="font-size: 0.85rem;">
                  <i class="fa-solid <?= e($h['icon'] ?: 'fa-brain') ?>"></i> <?= e($h['nama_kategori']) ?>
                </span>
              </td>
              <td><span style="font-weight: 800; font-size: 1.1rem; color: var(--primary-dark);"><?= (int)$h['total_skor'] ?></span></td>
              <td><strong><?= e($h['kategori_hasil']) ?></strong></td>
              <td><?= badge_risiko($h['kategori_hasil'], $h['tingkat_risiko']) ?></td>
              <td style="max-width: 250px;">
                <span class="muted" style="font-size: 0.85rem;">
                  <?= e(mb_strimwidth($h['rekomendasi'], 0, 75, '...')) ?>
                </span>
              </td>
              <td>
                <a href="<?= url('pasien/hasil.php?id=' . $h['id']) ?>" class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-file-lines"></i> Detail Hasil
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="text-center" style="padding: 40px 10px;">
      <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"></i>
      <h4>Belum Ada Data Riwayat</h4>
      <p class="muted" style="font-size: 0.9rem; margin-bottom: 18px;">
        Anda belum memiliki riwayat pemeriksaan pada kategori ini.
      </p>
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-primary btn-sm">
        Mulai Pemeriksaan Sekarang
      </a>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
