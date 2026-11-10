<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$pageTitle = 'Kelola Jadwal Praktik';
$activeMenu = 'jadwal';

$stmtPsi = db()->prepare('SELECT id FROM psikolog WHERE user_id = ?');
$stmtPsi->execute([$u['id']]);
$psiId = (int)$stmtPsi->fetchColumn();

// Tambah Slot Jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    csrf_check();

    $tanggal = trim($_POST['tanggal'] ?? '');
    $jamMulai = trim($_POST['jam_mulai'] ?? '');
    $jamSelesai = trim($_POST['jam_selesai'] ?? '');

    if (!$tanggal || $tanggal < date('Y-m-d')) {
        flash('Tanggal harus valid dan tidak boleh di masa lalu.', 'error');
    } elseif (!$jamMulai || !$jamSelesai || $jamSelesai <= $jamMulai) {
        flash('Jam selesai harus lebih akhir dari jam mulai.', 'error');
    } else {
        db()->prepare('
            INSERT INTO jadwal_psikolog (psikolog_id, tanggal, jam_mulai, jam_selesai, status)
            VALUES (?, ?, ?, ?, "tersedia")
        ')->execute([$psiId, $tanggal, $jamMulai . ':00', $jamSelesai . ':00']);

        flash('Slot jadwal ketersediaan berhasil ditambahkan.', 'success');
        redirect('psikolog/jadwal.php');
    }
}

// Hapus Slot Jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    csrf_check();
    $jadwalId = (int)($_POST['jadwal_id'] ?? 0);

    $stmtDel = db()->prepare('DELETE FROM jadwal_psikolog WHERE id = ? AND psikolog_id = ? AND status = "tersedia"');
    $stmtDel->execute([$jadwalId, $psiId]);

    if ($stmtDel->rowCount() > 0) {
        flash('Slot jadwal berhasil dihapus.', 'info');
    } else {
        flash('Jadwal tidak dapat dihapus karena sudah dipesan atau tidak ditemukan.', 'warning');
    }
    redirect('psikolog/jadwal.php');
}

// Ambil semua jadwal psikolog
$stmt = db()->prepare('
    SELECT * FROM jadwal_psikolog 
    WHERE psikolog_id = ? 
    ORDER BY tanggal DESC, jam_mulai ASC
');
$stmt->execute([$psiId]);
$schedules = $stmt->fetchAll();

require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<div class="grid grid-3" style="align-items: flex-start;">
  <!-- Form Tambah Slot Jadwal -->
  <div class="card" style="grid-column: span 1;">
    <h3 style="font-size: 1.2rem; margin-bottom: 14px;">
      <i class="fa-solid fa-calendar-plus text-primary"></i> Tambah Slot Praktik
    </h3>
    <p class="muted" style="font-size: 0.85rem; margin-bottom: 18px;">
      Tentukan slot waktu luang Anda agar pasien dapat memilih jadwal konsultasi langsung.
    </p>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah">

      <div class="form-group">
        <label class="form-label">Tanggal Praktik <span class="text-danger">*</span></label>
        <input type="date" name="tanggal" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
        <input type="time" name="jam_mulai" class="form-control" required value="09:00">
      </div>

      <div class="form-group">
        <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
        <input type="time" name="jam_selesai" class="form-control" required value="10:00">
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <i class="fa-solid fa-plus"></i> Tambah Slot Waktu
      </button>
    </form>
  </div>

  <!-- Tabel Daftar Slot Jadwal -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-calendar-days text-secondary"></i> Daftar Slot Jadwal Anda
      </div>
    </div>

    <?php if (!empty($schedules)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Rentang Waktu</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($schedules as $sc): ?>
              <tr>
                <td><strong><?= format_tanggal($sc['tanggal']) ?></strong></td>
                <td>
                  <i class="fa-regular fa-clock text-primary"></i>
                  <?= substr($sc['jam_mulai'], 0, 5) ?> - <?= substr($sc['jam_selesai'], 0, 5) ?> WIB
                </td>
                <td>
                  <?php if ($sc['status'] === 'tersedia'): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-check"></i> Tersedia</span>
                  <?php elseif ($sc['status'] === 'dipesan'): ?>
                    <span class="badge badge-warning"><i class="fa-solid fa-user-lock"></i> Dipesan Pasien</span>
                  <?php else: ?>
                    <span class="badge badge-secondary"><?= e($sc['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($sc['status'] === 'tersedia'): ?>
                    <form method="post" action="" onsubmit="return confirm('Hapus slot ketersediaan ini?');" style="display:inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="aksi" value="hapus">
                      <input type="hidden" name="jadwal_id" value="<?= $sc['id'] ?>">
                      <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)!important; border-color:var(--danger);">
                        <i class="fa-solid fa-trash"></i> Hapus
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="muted" style="font-size: 0.8rem;">Tidak dapat dihapus</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center" style="padding: 30px 0;">
        <i class="fa-regular fa-calendar-plus" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 10px;"></i>
        <p class="muted">Belum ada slot jadwal yang Anda daftarkan.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
