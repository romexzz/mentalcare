<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Kategori Assessment';
$activeMenu = 'kategori';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $nama = trim($_POST['nama'] ?? '');
        $kode = strtoupper(trim($_POST['kode'] ?? ''));
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $instruksi = trim($_POST['instruksi'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-brain');
        $status = trim($_POST['status'] ?? 'aktif');

        if (!$nama || !$kode) {
            flash('Nama kategori dan kode unik wajib diisi.', 'error');
        } else {
            if ($id > 0) {
                // Update
                $stmtCheck = db()->prepare('SELECT id FROM kategori_assessment WHERE kode = ? AND id != ?');
                $stmtCheck->execute([$kode, $id]);
                if ($stmtCheck->fetch()) {
                    flash('Kode kategori sudah digunakan.', 'error');
                } else {
                    db()->prepare('
                        UPDATE kategori_assessment 
                        SET nama = ?, kode = ?, deskripsi = ?, instruksi = ?, icon = ?, status = ?
                        WHERE id = ?
                    ')->execute([$nama, $kode, $deskripsi, $instruksi, $icon, $status, $id]);

                    flash('Kategori assessment berhasil diperbarui.', 'success');
                    redirect('admin/kategori.php');
                }
            } else {
                // Tambah baru
                $stmtCheck = db()->prepare('SELECT id FROM kategori_assessment WHERE kode = ?');
                $stmtCheck->execute([$kode]);
                if ($stmtCheck->fetch()) {
                    flash('Kode kategori sudah ada.', 'error');
                } else {
                    db()->prepare('
                        INSERT INTO kategori_assessment (nama, kode, deskripsi, instruksi, icon, status)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ')->execute([$nama, $kode, $deskripsi, $instruksi, $icon, $status]);

                    flash('Kategori assessment baru berhasil ditambahkan.', 'success');
                    redirect('admin/kategori.php');
                }
            }
        }
    } elseif ($aksi === 'toggle_status') {
        $stmtCurr = db()->prepare('SELECT status FROM kategori_assessment WHERE id = ?');
        $stmtCurr->execute([$id]);
        $curr = $stmtCurr->fetchColumn();
        if ($curr) {
            $newStatus = ($curr === 'aktif') ? 'nonaktif' : 'aktif';
            db()->prepare('UPDATE kategori_assessment SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            flash("Status kategori diubah menjadi $newStatus.", 'info');
        }
        redirect('admin/kategori.php');
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM kategori_assessment WHERE id = ?')->execute([$id]);
        flash('Kategori assessment beserta seluruh pertanyaan dan aturan skornya telah dihapus.', 'info');
        redirect('admin/kategori.php');
    }
}

// Data edit
$editKat = null;
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM kategori_assessment WHERE id = ?');
    $stmt->execute([$id]);
    $editKat = $stmt->fetch();
}

// Ambil semua kategori beserta jumlah pertanyaan dan aturan skor
$stmtList = db()->query('
    SELECT k.*, 
           COUNT(DISTINCT p.id) as total_pertanyaan,
           COUNT(DISTINCT a.id) as total_aturan
    FROM kategori_assessment k
    LEFT JOIN pertanyaan p ON p.kategori_id = k.id
    LEFT JOIN kategori_skor_aturan a ON a.kategori_id = k.id
    GROUP BY k.id
    ORDER BY k.id ASC
');
$kategoriList = $stmtList->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Manajemen Kategori Assessment (Kuisioner)</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Atur kategori instrumen pemeriksaan kesehatan mental (Stres, Kecemasan, Depresi, Burnout, dll).
      </p>
    </div>
    <div>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalFormKategori').style.display='block'">
        <i class="fa-solid fa-plus"></i> Tambah Kategori Baru
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Kategori</th>
          <th>Kode</th>
          <th>Deskripsi Singkat</th>
          <th>Total Pertanyaan</th>
          <th>Aturan Skor</th>
          <th>Status</th>
          <th>Aksi & Kelola</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kategoriList as $idx => $k): ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td>
              <strong style="color: var(--primary-dark); font-size: 1.05rem;">
                <i class="fa-solid <?= e($k['icon'] ?: 'fa-brain') ?> text-primary"></i> <?= e($k['nama']) ?>
              </strong>
            </td>
            <td><code><?= e($k['kode']) ?></code></td>
            <td style="max-width: 240px;"><small class="muted"><?= e(mb_strimwidth($k['deskripsi'], 0, 75, '...')) ?></small></td>
            <td>
              <a href="<?= url('admin/pertanyaan.php?kategori_id=' . $k['id']) ?>" class="badge badge-info" title="Klik untuk mengelola pertanyaan">
                <i class="fa-solid fa-list-check"></i> <?= (int)$k['total_pertanyaan'] ?> Pertanyaan
              </a>
            </td>
            <td>
              <a href="<?= url('admin/aturan_skor.php?kategori_id=' . $k['id']) ?>" class="badge badge-warning" title="Klik untuk mengelola rentang skor">
                <i class="fa-solid fa-sliders"></i> <?= (int)$k['total_aturan'] ?> Aturan Skor
              </a>
            </td>
            <td><?= badge_status($k['status']) ?></td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="<?= url('admin/pertanyaan.php?kategori_id=' . $k['id']) ?>" class="btn btn-primary btn-sm" title="Kelola Butir Pertanyaan">
                  <i class="fa-solid fa-list-check"></i>
                </a>

                <a href="<?= url('admin/aturan_skor.php?kategori_id=' . $k['id']) ?>" class="btn btn-secondary btn-sm" title="Kelola Rentang Skor">
                  <i class="fa-solid fa-sliders"></i>
                </a>

                <a href="<?= url('admin/kategori.php?action=edit&id=' . $k['id']) ?>" class="btn btn-light btn-sm" title="Edit Kategori">
                  <i class="fa-solid fa-pen-to-square"></i>
                </a>

                <!-- Toggle Aktif -->
                <form method="post" action="" style="display: inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $k['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm" title="<?= $k['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                    <i class="fa-solid <?= $k['status'] === 'aktif' ? 'fa-ban text-danger' : 'fa-check text-success' ?>"></i>
                  </button>
                </form>

                <!-- Hapus -->
                <form method="post" action="" onsubmit="return confirm('Hapus kategori ini beserta pertanyaan dan aturan penilaiannya?');" style="display: inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= $k['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)!important; border-color:var(--danger);" title="Hapus">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah / Edit Kategori -->
<div id="modalFormKategori" style="display: <?= $editKat ? 'block' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 600px; margin: 40px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;"><?= $editKat ? 'Edit Kategori Assessment' : 'Tambah Kategori Assessment' ?></h3>
      <button type="button" onclick="document.getElementById('modalFormKategori').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int)($editKat['id'] ?? 0) ?>">

      <div class="form-row">
        <div class="form-group" style="flex: 2;">
          <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" required placeholder="Contoh: Stres, Kecemasan (GAD-7), Burnout" value="<?= e($editKat['nama'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Kode Unik <span class="text-danger">*</span></label>
          <input type="text" name="kode" class="form-control" required placeholder="STRESS, GAD7" value="<?= e($editKat['kode'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Deskripsi Kategori</label>
        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsikan fungsi dan tujuan kuisioner..."><?= e($editKat['deskripsi'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Instruksi Pengisian Pasien</label>
        <textarea name="instruksi" class="form-control" rows="2" placeholder="Petunjuk waktu atau cara menjawab yang ditampilkan kepada pasien..."><?= e($editKat['instruksi'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Icon FontAwesome</label>
          <input type="text" name="icon" class="form-control" placeholder="fa-brain, fa-bolt, fa-fire" value="<?= e($editKat['icon'] ?? 'fa-brain') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="aktif" <?= ($editKat['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= ($editKat['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalFormKategori').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Kategori</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
