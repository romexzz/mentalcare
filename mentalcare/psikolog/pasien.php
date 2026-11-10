<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$pageTitle = 'Daftar Pasien';
$activeMenu = 'pasien';

$stmtPsi = db()->prepare('SELECT id FROM psikolog WHERE user_id = ?');
$stmtPsi->execute([$u['id']]);
$psiId = (int)$stmtPsi->fetchColumn();

$cari = trim($_GET['cari'] ?? '');

$query = '
    SELECT u.id as pasien_id, u.nama, u.email, u.no_hp, u.jenis_kelamin, u.tanggal_lahir,
           COUNT(c.id) as total_sesi,
           MAX(c.tanggal) as tanggal_terakhir,
           MAX(c.izinkan_akses_riwayat) as izin_akses
    FROM konsultasi c
    JOIN users u ON u.id = c.pasien_id
    WHERE c.psikolog_id = ?
';
$params = [$psiId];

if ($cari !== '') {
    $query .= ' AND (u.nama LIKE ? OR u.email LIKE ? OR u.no_hp LIKE ?)';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

$query .= ' GROUP BY u.id, u.nama, u.email, u.no_hp, u.jenis_kelamin, u.tanggal_lahir ORDER BY tanggal_terakhir DESC';

$stmt = db()->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Pasien Bimbingan Anda</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Daftar seluruh pasien yang terdaftar dalam sesi konsultasi bersama Anda.
      </p>
    </div>

    <!-- Form Pencarian -->
    <form method="get" action="" style="display: flex; gap: 8px;">
      <input type="text" name="cari" class="form-control" style="width: 240px; padding: 6px 12px; font-size: 0.875rem;" placeholder="Cari nama atau email..." value="<?= e($cari) ?>">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i> Cari</button>
      <?php if ($cari): ?>
        <a href="<?= url('psikolog/pasien.php') ?>" class="btn btn-light btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (!empty($patients)): ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Nama Pasien</th>
            <th>Kontak & Email</th>
            <th>Jenis Kelamin</th>
            <th>Total Sesi</th>
            <th>Sesi Terakhir</th>
            <th>Izin Akses Riwayat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($patients as $p): ?>
            <tr>
              <td>
                <strong style="color: var(--primary-dark); font-size: 1rem;"><?= e($p['nama']) ?></strong>
              </td>
              <td>
                <div><i class="fa-solid fa-envelope text-primary"></i> <?= e($p['email']) ?></div>
                <div class="muted" style="font-size: 0.8rem;"><i class="fa-brands fa-whatsapp text-success"></i> <?= e($p['no_hp'] ?: '-') ?></div>
              </td>
              <td><?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($p['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></td>
              <td><span class="badge badge-info"><?= (int)$p['total_sesi'] ?> Sesi</span></td>
              <td><?= format_tanggal($p['tanggal_terakhir']) ?></td>
              <td>
                <?php if ($p['izin_akses']): ?>
                  <span class="badge badge-success"><i class="fa-solid fa-shield-halved"></i> Diizinkan</span>
                <?php else: ?>
                  <span class="badge badge-secondary"><i class="fa-solid fa-lock"></i> Tertutup</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= url('psikolog/pasien_detail.php?id=' . $p['pasien_id']) ?>" class="btn btn-primary btn-sm">
                  <i class="fa-solid fa-folder-open"></i> Rekam Medis & Riwayat
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="text-center" style="padding: 40px 10px;">
      <i class="fa-solid fa-user-slash" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"></i>
      <h4>Belum Ada Pasien</h4>
      <p class="muted" style="font-size: 0.9rem;">
        Belum ada pasien yang berkonsultasi dengan Anda atau sesuai dengan kata kunci pencarian.
      </p>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
