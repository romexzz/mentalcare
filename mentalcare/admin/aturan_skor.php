<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Aturan Rentang Skor & Rekomendasi';
$activeMenu = 'aturan_skor';

$kategori_id = (int)($_GET['kategori_id'] ?? $_POST['kategori_id'] ?? 0);
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Ambil semua kategori
$categories = db()->query('SELECT id, nama, kode FROM kategori_assessment ORDER BY id ASC')->fetchAll();

if ($kategori_id === 0 && !empty($categories)) {
    $kategori_id = (int)$categories[0]['id'];
}

// Simpan Tambah / Edit Aturan Skor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_aturan') {
        $katId = (int)($_POST['kategori_id'] ?? 0);
        $skor_min = (int)($_POST['skor_min'] ?? 0);
        $skor_max = (int)($_POST['skor_max'] ?? 0);
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $tingkat_risiko = trim($_POST['tingkat_risiko'] ?? '');
        $warna_badge = trim($_POST['warna_badge'] ?? 'success');
        $saran_rekomendasi = trim($_POST['saran_rekomendasi'] ?? '');

        if ($katId <= 0 || empty($nama_kategori) || empty($saran_rekomendasi)) {
            flash('Harap lengkapi semua data aturan skor dan saran rekomendasi.', 'error');
        } elseif ($skor_max < $skor_min) {
            flash('Skor maksimum harus lebih besar atau sama dengan skor minimum.', 'error');
        } else {
            if ($id > 0) {
                // Update
                db()->prepare('
                    UPDATE kategori_skor_aturan 
                    SET kategori_id = ?, skor_min = ?, skor_max = ?, nama_kategori = ?, tingkat_risiko = ?, warna_badge = ?, saran_rekomendasi = ?
                    WHERE id = ?
                ')->execute([$katId, $skor_min, $skor_max, $nama_kategori, $tingkat_risiko, $warna_badge, $saran_rekomendasi, $id]);

                flash('Aturan rentang skor berhasil diperbarui.', 'success');
            } else {
                // Tambah baru
                db()->prepare('
                    INSERT INTO kategori_skor_aturan (kategori_id, skor_min, skor_max, nama_kategori, tingkat_risiko, warna_badge, saran_rekomendasi)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ')->execute([$katId, $skor_min, $skor_max, $nama_kategori, $tingkat_risiko, $warna_badge, $saran_rekomendasi]);

                flash('Aturan rentang skor baru berhasil ditambahkan.', 'success');
            }
            redirect('admin/aturan_skor.php?kategori_id=' . $katId);
        }
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM kategori_skor_aturan WHERE id = ?')->execute([$id]);
        flash('Aturan rentang skor berhasil dihapus.', 'info');
        redirect('admin/aturan_skor.php?kategori_id=' . $kategori_id);
    }
}

// Data edit
$editRule = null;
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM kategori_skor_aturan WHERE id = ?');
    $stmt->execute([$id]);
    $editRule = $stmt->fetch();
}

// Ambil aturan untuk kategori terpilih
$stmtRules = db()->prepare('
    SELECT * FROM kategori_skor_aturan 
    WHERE kategori_id = ? 
    ORDER BY skor_min ASC
');
$stmtRules->execute([$kategori_id]);
$rules = $stmtRules->fetchAll();

// Ambil info kategori aktif
$stmtCurrKat = db()->prepare('SELECT * FROM kategori_assessment WHERE id = ?');
$stmtCurrKat->execute([$kategori_id]);
$currentKat = $stmtCurrKat->fetch();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Aturan Penilaian Skor & Rekomendasi</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Kategori: <strong><?= e($currentKat['nama'] ?? 'Semua') ?></strong> — Pengaturan dinamis tanpa ubah kode!
      </p>
    </div>
    <div>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalFormRule').style.display='block'">
        <i class="fa-solid fa-plus"></i> Tambah Rentang Skor
      </button>
    </div>
  </div>

  <!-- Pilihan Filter Kategori -->
  <div style="margin-bottom: 24px; display: flex; gap: 8px; flex-wrap: wrap;">
    <?php foreach ($categories as $cat): ?>
      <a href="<?= url('admin/aturan_skor.php?kategori_id=' . $cat['id']) ?>" class="btn <?= $kategori_id === (int)$cat['id'] ? 'btn-primary' : 'btn-light' ?> btn-sm">
        <?= e($cat['nama']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 140px;">Rentang Skor</th>
          <th>Nama Kategori</th>
          <th>Tingkat Risiko</th>
          <th>Saran & Rekomendasi Otomatis</th>
          <th style="width: 100px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rules)): ?>
          <?php foreach ($rules as $r): ?>
            <tr>
              <td>
                <span class="badge badge-secondary" style="font-size: 0.95rem; font-weight: 800;">
                  <?= (int)$r['skor_min'] ?> &ndash; <?= (int)$r['skor_max'] ?>
                </span>
              </td>
              <td>
                <strong style="font-size: 1rem; color: var(--primary-dark);"><?= e($r['nama_kategori']) ?></strong>
              </td>
              <td><?= badge_risiko($r['nama_kategori'], $r['tingkat_risiko']) ?></td>
              <td style="font-size: 0.875rem; color: var(--text-main); max-width: 380px;">
                <?= nl2br(e($r['saran_rekomendasi'])) ?>
              </td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= url('admin/aturan_skor.php?action=edit&id=' . $r['id'] . '&kategori_id=' . $kategori_id) ?>" class="btn btn-light btn-sm" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>

                  <form method="post" action="" onsubmit="return confirm('Hapus aturan rentang skor ini?');" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="kategori_id" value="<?= $kategori_id ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)!important; border-color:var(--danger);" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center muted" style="padding: 24px;">Belum ada aturan skor yang didaftarkan pada kategori ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah / Edit Aturan Skor -->
<div id="modalFormRule" style="display: <?= $editRule ? 'block' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 600px; margin: 40px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;"><?= $editRule ? 'Edit Aturan Rentang Skor' : 'Tambah Aturan Rentang Skor' ?></h3>
      <button type="button" onclick="document.getElementById('modalFormRule').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan_aturan">
      <input type="hidden" name="id" value="<?= (int)($editRule['id'] ?? 0) ?>">

      <div class="form-group">
        <label class="form-label">Kategori Assessment <span class="text-danger">*</span></label>
        <select name="kategori_id" class="form-control" required>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($editRule['kategori_id'] ?? $kategori_id) === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Skor Minimal <span class="text-danger">*</span></label>
          <input type="number" name="skor_min" class="form-control" required min="0" value="<?= (int)($editRule['skor_min'] ?? 0) ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Skor Maksimal <span class="text-danger">*</span></label>
          <input type="number" name="skor_max" class="form-control" required min="0" value="<?= (int)($editRule['skor_max'] ?? 9) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nama Kategori Hasil <span class="text-danger">*</span></label>
          <input type="text" name="nama_kategori" class="form-control" required placeholder="Normal / Ringan / Sedang / Tinggi" value="<?= e($editRule['nama_kategori'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Tingkat Risiko <span class="text-danger">*</span></label>
          <input type="text" name="tingkat_risiko" class="form-control" required placeholder="Rendah / Sedang / Perlu diperhatikan / Tinggi" value="<?= e($editRule['tingkat_risiko'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Saran & Rekomendasi Otomatis untuk Pasien <span class="text-danger">*</span></label>
        <textarea name="saran_rekomendasi" class="form-control" rows="4" required placeholder="Tuliskan rekomendasi tindakan, gaya hidup, pola tidur, dan saran konsultasi..."><?= e($editRule['saran_rekomendasi'] ?? '') ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalFormRule').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Aturan Skor</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
