<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Seluruh Konsultasi';
$activeMenu = 'konsultasi';

// Update status atau tautan oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'update_status') {
        $status = trim($_POST['status'] ?? '');
        $link = trim($_POST['link_konsultasi'] ?? '');

        if ($id > 0 && in_array($status, ['menunggu', 'disetujui', 'berlangsung', 'selesai', 'dibatalkan'])) {
            db()->prepare('UPDATE konsultasi SET status = ?, link_konsultasi = ? WHERE id = ?')
                ->execute([$status, $link ?: null, $id]);
            flash('Status dan informasi konsultasi berhasil diperbarui.', 'success');
        }
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM konsultasi WHERE id = ?')->execute([$id]);
        flash('Data konsultasi telah dihapus.', 'info');
    }
    redirect('admin/konsultasi.php');
}

// Filter
$statusFilter = trim($_GET['status'] ?? '');
$psikologFilter = (int)($_GET['psikolog_id'] ?? 0);

$query = '
    SELECT c.*, up.nama as nama_pasien, up.email as email_pasien, up.no_hp as hp_pasien,
           ups.nama as nama_psikolog, psi.spesialisasi
    FROM konsultasi c
    JOIN users up ON up.id = c.pasien_id
    JOIN psikolog psi ON psi.id = c.psikolog_id
    JOIN users ups ON ups.id = psi.user_id
    WHERE 1=1
';
$params = [];

if ($statusFilter) {
    $query .= ' AND c.status = ?';
    $params[] = $statusFilter;
}
if ($psikologFilter > 0) {
    $query .= ' AND c.psikolog_id = ?';
    $params[] = $psikologFilter;
}
$query .= ' ORDER BY c.tanggal DESC, c.waktu DESC';

$stmt = db()->prepare($query);
$stmt->execute($params);
$consultations = $stmt->fetchAll();

// Daftar psikolog untuk dropdown filter
$psikologs = db()->query('
    SELECT p.id, u.nama 
    FROM psikolog p 
    JOIN users u ON u.id = p.user_id 
    ORDER BY u.nama ASC
')->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Manajemen Seluruh Sesi Konsultasi</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Pantau interaksi konsultasi antara pasien dan psikolog di seluruh sistem.
      </p>
    </div>
  </div>

  <!-- Filter Form -->
  <form method="get" action="" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end;">
    <div style="min-width: 180px;">
      <label class="form-label" style="font-size: 0.825rem;">Filter Status:</label>
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="menunggu" <?= $statusFilter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
        <option value="disetujui" <?= $statusFilter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
        <option value="berlangsung" <?= $statusFilter === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
        <option value="selesai" <?= $statusFilter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
        <option value="dibatalkan" <?= $statusFilter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
      </select>
    </div>

    <div style="min-width: 220px;">
      <label class="form-label" style="font-size: 0.825rem;">Filter Psikolog:</label>
      <select name="psikolog_id" class="form-control" onchange="this.form.submit()">
        <option value="0">Semua Psikolog</option>
        <?php foreach ($psikologs as $psi): ?>
          <option value="<?= $psi['id'] ?>" <?= $psikologFilter === (int)$psi['id'] ? 'selected' : '' ?>>
            <?= e($psi['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if ($statusFilter || $psikologFilter > 0): ?>
      <a href="<?= url('admin/konsultasi.php') ?>" class="btn btn-light btn-sm" style="margin-bottom: 2px;">
        <i class="fa-solid fa-rotate-left"></i> Reset
      </a>
    <?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Tanggal & Jam</th>
          <th>Pasien</th>
          <th>Psikolog</th>
          <th>Metode</th>
          <th>Keluhan</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($consultations)): ?>
          <?php foreach ($consultations as $c): ?>
            <tr>
              <td>
                <strong><?= format_tanggal($c['tanggal']) ?></strong><br>
                <small class="muted"><i class="fa-regular fa-clock"></i> <?= substr($c['waktu'], 0, 5) ?> WIB</small>
              </td>
              <td>
                <strong><?= e($c['nama_pasien']) ?></strong><br>
                <small class="muted"><?= e($c['hp_pasien'] ?: $c['email_pasien']) ?></small>
              </td>
              <td>
                <strong style="color: var(--primary-dark);"><?= e($c['nama_psikolog']) ?></strong><br>
                <small class="muted"><?= e($c['spesialisasi']) ?></small>
              </td>
              <td><span class="badge badge-light"><?= e($c['metode']) ?></span></td>
              <td style="max-width: 200px;">
                <span class="muted" style="font-size: 0.825rem;">
                  <?= e(mb_strimwidth($c['keluhan'], 0, 60, '...')) ?>
                </span>
              </td>
              <td><?= badge_status($c['status']) ?></td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <button type="button" class="btn btn-light btn-sm" onclick="editKonsultasi(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)" title="Ubah Status & Link">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>

                  <form method="post" action="" onsubmit="return confirm('Hapus data konsultasi ini?');" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger)!important; border-color: var(--danger);" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center muted" style="padding: 24px;">Tidak ada data konsultasi ditemukan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Update Konsultasi Admin -->
<div id="modalEditKonsultasi" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 500px; margin: 50px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;">Atur Status Konsultasi</h3>
      <button type="button" onclick="document.getElementById('modalEditKonsultasi').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="update_status">
      <input type="hidden" name="id" id="editKonsulId" value="0">

      <div class="form-group">
        <label class="form-label">Status Sesi</label>
        <select name="status" id="editKonsulStatus" class="form-control">
          <option value="menunggu">Menunggu</option>
          <option value="disetujui">Disetujui</option>
          <option value="berlangsung">Berlangsung</option>
          <option value="selesai">Selesai</option>
          <option value="dibatalkan">Dibatalkan</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tautan Ruang Konsultasi (Google Meet / Zoom)</label>
        <input type="text" name="link_konsultasi" id="editKonsulLink" class="form-control" placeholder="https://meet.google.com/...">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalEditKonsultasi').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function editKonsultasi(data) {
  document.getElementById('editKonsulId').value = data.id;
  document.getElementById('editKonsulStatus').value = data.status;
  document.getElementById('editKonsulLink').value = data.link_konsultasi || '';
  document.getElementById('modalEditKonsultasi').style.display = 'block';
}
</script>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
