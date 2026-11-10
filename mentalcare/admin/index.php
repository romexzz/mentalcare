<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Dashboard & Statistik Sistem';
$activeMenu = 'dashboard';

// Helper query hitung
$count = fn(string $sql, array $params = []) => (int)db()->prepare($sql)->execute($params) ? (int)db()->prepare($sql)->fetchColumn() : 0;

$totalPasien = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'pasien'")->fetchColumn();
$totalPsikolog = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'psikolog'")->fetchColumn();
$totalPemeriksaan = (int)db()->query("SELECT COUNT(*) FROM pemeriksaan")->fetchColumn();
$totalKonsultasi = (int)db()->query("SELECT COUNT(*) FROM konsultasi")->fetchColumn();
$totalAktif = (int)db()->query("SELECT COUNT(*) FROM users WHERE status = 'aktif'")->fetchColumn();

// Data Grafik 1: Pemeriksaan berdasarkan Kategori
$stmtDistKat = db()->query('
    SELECT k.nama, COUNT(p.id) as total
    FROM kategori_assessment k
    LEFT JOIN pemeriksaan p ON p.kategori_id = k.id
    GROUP BY k.id, k.nama
    ORDER BY k.id ASC
');
$distKategori = $stmtDistKat->fetchAll();

// Data Grafik 2: Pasien berdasarkan Jenis Kelamin
$stmtGender = db()->query('
    SELECT 
        SUM(CASE WHEN jenis_kelamin = "L" THEN 1 ELSE 0 END) as pria,
        SUM(CASE WHEN jenis_kelamin = "P" THEN 1 ELSE 0 END) as wanita,
        SUM(CASE WHEN jenis_kelamin NOT IN ("L", "P") OR jenis_kelamin IS NULL THEN 1 ELSE 0 END) as lainnya
    FROM users WHERE role = "pasien"
');
$distGender = $stmtGender->fetch();

// Data Grafik 3: Konsultasi berdasarkan Status
$stmtStatKonsul = db()->query('
    SELECT status, COUNT(*) as total 
    FROM konsultasi 
    GROUP BY status
');
$distKonsul = $stmtStatKonsul->fetchAll();

// Tabel Aktivitas: 5 Pemeriksaan Terkini
$stmtRecentExam = db()->query('
    SELECT p.*, u.nama as nama_pasien, k.nama as nama_kategori
    FROM pemeriksaan p
    JOIN users u ON u.id = p.pasien_id
    JOIN kategori_assessment k ON k.id = p.kategori_id
    ORDER BY p.tanggal_pemeriksaan DESC
    LIMIT 5
');
$recentExams = $stmtRecentExam->fetchAll();

// Tabel Aktivitas: 5 Konsultasi Terkini
$stmtRecentConsult = db()->query('
    SELECT c.*, up.nama as nama_pasien, ups.nama as nama_psikolog
    FROM konsultasi c
    JOIN users up ON up.id = c.pasien_id
    JOIN psikolog p ON p.id = c.psikolog_id
    JOIN users ups ON ups.id = p.user_id
    ORDER BY c.created_at DESC
    LIMIT 5
');
$recentConsults = $stmtRecentConsult->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Stat Cards -->
<div class="grid grid-4" style="margin-bottom: 24px;">
  <div class="stat-card">
    <div class="stat-icon blue">
      <i class="fa-solid fa-hospital-user"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalPasien ?></h3>
      <p>Total Pasien</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <i class="fa-solid fa-user-doctor"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalPsikolog ?></h3>
      <p>Total Psikolog</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber">
      <i class="fa-solid fa-clipboard-check"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalPemeriksaan ?></h3>
      <p>Pemeriksaan Dilakukan</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">
      <i class="fa-solid fa-calendar-check"></i>
    </div>
    <div class="stat-info">
      <h3><?= $totalKonsultasi ?></h3>
      <p>Total Konsultasi</p>
    </div>
  </div>
</div>

<!-- Visualisasi Grafik Interaktif (Chart.js) -->
<div class="grid grid-3" style="margin-bottom: 24px;">
  <!-- Grafik 1: Pemeriksaan per Kategori -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-chart-bar text-primary"></i> Pemeriksaan Berdasarkan Kategori
      </div>
    </div>
    <div style="height: 280px; position: relative;">
      <canvas id="chartKategori"></canvas>
    </div>
  </div>

  <!-- Grafik 2: Gender Pasien -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-chart-pie text-secondary"></i> Demografi Gender Pasien
      </div>
    </div>
    <div style="height: 280px; position: relative;">
      <canvas id="chartGender"></canvas>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <!-- 5 Pemeriksaan Terbaru -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-clipboard-list text-primary"></i> Pemeriksaan Terkini
      </div>
      <a href="<?= url('admin/laporan.php') ?>" class="btn btn-light btn-sm">Laporan Rekap</a>
    </div>

    <?php if (!empty($recentExams)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Pasien</th>
              <th>Assessment</th>
              <th>Skor</th>
              <th>Hasil</th>
              <th>Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentExams as $re): ?>
              <tr>
                <td><strong><?= e($re['nama_pasien']) ?></strong></td>
                <td><?= e($re['nama_kategori']) ?></td>
                <td><span style="font-weight: 700;"><?= (int)$re['total_skor'] ?></span></td>
                <td><?= badge_risiko($re['kategori_hasil'], $re['tingkat_risiko']) ?></td>
                <td><small class="muted"><?= format_tanggal($re['tanggal_pemeriksaan']) ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 20px 0;">Belum ada pemeriksaan tercatat.</p>
    <?php endif; ?>
  </div>

  <!-- 5 Konsultasi Terbaru -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-comments text-secondary"></i> Konsultasi Terkini
      </div>
      <a href="<?= url('admin/konsultasi.php') ?>" class="btn btn-light btn-sm">Kelola Semua</a>
    </div>

    <?php if (!empty($recentConsults)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Pasien</th>
              <th>Psikolog</th>
              <th>Tanggal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentConsults as $rc): ?>
              <tr>
                <td><strong><?= e($rc['nama_pasien']) ?></strong></td>
                <td><?= e($rc['nama_psikolog']) ?></td>
                <td><?= format_tanggal($rc['tanggal']) ?></td>
                <td><?= badge_status($rc['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 20px 0;">Belum ada konsultasi tercatat.</p>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Chart 1: Pemeriksaan per Kategori
  const ctxKat = document.getElementById('chartKategori').getContext('2d');
  new Chart(ctxKat, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($distKategori, 'nama')) ?>,
      datasets: [{
        label: 'Jumlah Pemeriksaan',
        data: <?= json_encode(array_map('intval', array_column($distKategori, 'total'))) ?>,
        backgroundColor: '#3b8276',
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // Chart 2: Pasien Berdasarkan Gender
  const ctxGen = document.getElementById('chartGender').getContext('2d');
  new Chart(ctxGen, {
    type: 'doughnut',
    data: {
      labels: ['Laki-laki', 'Perempuan', 'Belum Diisi'],
      datasets: [{
        data: [
          <?= (int)($distGender['pria'] ?? 0) ?>,
          <?= (int)($distGender['wanita'] ?? 0) ?>,
          <?= (int)($distGender['lainnya'] ?? 0) ?>
        ],
        backgroundColor: ['#0284c7', '#ec4899', '#cbd5e1']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false
    }
  });
});
</script>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
