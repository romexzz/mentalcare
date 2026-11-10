<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Pertanyaan Kuisioner';
$activeMenu = 'pertanyaan';

$kategori_id = (int)($_GET['kategori_id'] ?? $_POST['kategori_id'] ?? 0);
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Ambil semua kategori untuk filter dan select
$categories = db()->query('SELECT id, nama, kode FROM kategori_assessment ORDER BY id ASC')->fetchAll();

if ($kategori_id === 0 && !empty($categories)) {
    $kategori_id = (int)$categories[0]['id'];
}

// Proses Tambah / Edit Pertanyaan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_pertanyaan') {
        $katId = (int)($_POST['kategori_id'] ?? 0);
        $pertanyaanTeks = trim($_POST['pertanyaan'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);
        $kritis = !empty($_POST['kritis']) ? 1 : 0;
        $status = trim($_POST['status'] ?? 'aktif');

        if ($katId <= 0 || empty($pertanyaanTeks)) {
            flash('Kategori dan teks pertanyaan wajib diisi.', 'error');
        } else {
            $pdo = db();
            if ($id > 0) {
                // Update
                $pdo->prepare('
                    UPDATE pertanyaan 
                    SET kategori_id = ?, pertanyaan = ?, urutan = ?, kritis = ?, status = ?
                    WHERE id = ?
                ')->execute([$katId, $pertanyaanTeks, $urutan, $kritis, $status, $id]);

                flash('Pertanyaan berhasil diperbarui.', 'success');
            } else {
                // Tambah baru
                $pdo->prepare('
                    INSERT INTO pertanyaan (kategori_id, pertanyaan, urutan, kritis, status)
                    VALUES (?, ?, ?, ?, ?)
                ')->execute([$katId, $pertanyaanTeks, $urutan, $kritis, $status]);
                $newQId = $pdo->lastInsertId();

                // Tambahkan 5 skala pilihan standar otomatis
                $stmtOpt = $pdo->prepare('
                    INSERT INTO pilihan_jawaban (pertanyaan_id, pilihan, skor, urutan)
                    VALUES (?, ?, ?, ?)
                ');
                $standardScales = [
                    ['Tidak pernah', 0, 1],
                    ['Jarang', 1, 2],
                    ['Kadang-kadang', 2, 3],
                    ['Sering', 3, 4],
                    ['Sangat sering', 4, 5],
                ];
                foreach ($standardScales as [$pilihan, $skor, $urut]) {
                    $stmtOpt->execute([$newQId, $pilihan, $skor, $urut]);
                }

                flash('Pertanyaan baru beserta 5 skala jawaban standar berhasil ditambahkan.', 'success');
            }
            redirect('admin/pertanyaan.php?kategori_id=' . $katId);
        }
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM pertanyaan WHERE id = ?')->execute([$id]);
        flash('Pertanyaan berhasil dihapus.', 'info');
        redirect('admin/pertanyaan.php?kategori_id=' . $kategori_id);
    }
}

// Data edit
$editQ = null;
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM pertanyaan WHERE id = ?');
    $stmt->execute([$id]);
    $editQ = $stmt->fetch();
}

// Ambil pertanyaan untuk kategori terpilih
$stmtQuestions = db()->prepare('
    SELECT p.*, COUNT(pj.id) as total_pilihan
    FROM pertanyaan p
    LEFT JOIN pilihan_jawaban pj ON pj.pertanyaan_id = p.id
    WHERE p.kategori_id = ?
    GROUP BY p.id
    ORDER BY p.urutan ASC, p.id ASC
');
$stmtQuestions->execute([$kategori_id]);
$questions = $stmtQuestions->fetchAll();

// Ambil info kategori aktif
$stmtCurrKat = db()->prepare('SELECT * FROM kategori_assessment WHERE id = ?');
$stmtCurrKat->execute([$kategori_id]);
$currentKat = $stmtCurrKat->fetch();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Kelola Butir Pertanyaan Kuisioner</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Kategori: <strong><?= e($currentKat['nama'] ?? 'Semua') ?></strong>
      </p>
    </div>
    <div style="display: flex; gap: 8px;">
      <a href="<?= url('admin/aturan_skor.php?kategori_id=' . $kategori_id) ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-sliders"></i> Atur Rentang Skor
      </a>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalFormQ').style.display='block'">
        <i class="fa-solid fa-plus"></i> Tambah Pertanyaan
      </button>
    </div>
  </div>

  <!-- Pilihan Filter Kategori -->
  <div style="margin-bottom: 24px; display: flex; gap: 8px; flex-wrap: wrap;">
    <?php foreach ($categories as $cat): ?>
      <a href="<?= url('admin/pertanyaan.php?kategori_id=' . $cat['id']) ?>" class="btn <?= $kategori_id === (int)$cat['id'] ? 'btn-primary' : 'btn-light' ?> btn-sm">
        <?= e($cat['nama']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 60px;">Urutan</th>
          <th>Teks Pertanyaan</th>
          <th style="width: 140px;">Pilihan Jawaban</th>
          <th style="width: 120px;">Indikator Kritis</th>
          <th style="width: 90px;">Status</th>
          <th style="width: 120px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($questions)): ?>
          <?php foreach ($questions as $q): ?>
            <tr>
              <td style="font-weight: 700; text-align: center;"><?= (int)$q['urutan'] ?></td>
              <td>
                <div style="font-weight: 600; color: var(--primary-dark); font-size: 0.95rem;">
                  <?= e($q['pertanyaan']) ?>
                </div>
              </td>
              <td>
                <span class="badge badge-info" style="font-size: 0.75rem;">
                  <i class="fa-solid fa-circle-dot"></i> <?= (int)$q['total_pilihan'] ?> Pilihan
                </span>
              </td>
              <td>
                <?php if ($q['kritis']): ?>
                  <span class="badge badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> Kritis</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Standar</span>
                <?php endif; ?>
              </td>
              <td><?= badge_status($q['status']) ?></td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= url('admin/pertanyaan.php?action=edit&id=' . $q['id'] . '&kategori_id=' . $kategori_id) ?>" class="btn btn-light btn-sm" title="Edit Pertanyaan">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>

                  <form method="post" action="" onsubmit="return confirm('Hapus pertanyaan ini?');" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $q['id'] ?>">
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
          <tr><td colspan="6" class="text-center muted" style="padding: 24px;">Belum ada pertanyaan pada kategori ini. Klik "Tambah Pertanyaan" untuk memulai.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah / Edit Pertanyaan -->
<div id="modalFormQ" style="display: <?= $editQ ? 'block' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 600px; margin: 40px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;"><?= $editQ ? 'Edit Butir Pertanyaan' : 'Tambah Pertanyaan Kuisioner' ?></h3>
      <button type="button" onclick="document.getElementById('modalFormQ').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan_pertanyaan">
      <input type="hidden" name="id" value="<?= (int)($editQ['id'] ?? 0) ?>">

      <div class="form-group">
        <label class="form-label">Kategori Assessment <span class="text-danger">*</span></label>
        <select name="kategori_id" class="form-control" required>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($editQ['kategori_id'] ?? $kategori_id) === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Teks Pertanyaan <span class="text-danger">*</span></label>
        <textarea name="pertanyaan" class="form-control" rows="3" required placeholder="Tuliskan butir pertanyaan atau pernyataan gejala..."><?= e($editQ['pertanyaan'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor Urut</label>
          <input type="number" name="urutan" class="form-control" min="1" value="<?= (int)($editQ['urutan'] ?? (count($questions) + 1)) ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="aktif" <?= ($editQ['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= ($editQ['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
      </div>

      <div style="background: var(--bg-body); padding: 12px 16px; border-radius: var(--radius-sm); margin: 16px 0;">
        <label style="display: flex; gap: 10px; align-items: center; cursor: pointer; font-size: 0.9rem; margin: 0; font-weight: 600;">
          <input type="checkbox" name="kritis" value="1" <?= !empty($editQ['kritis']) ? 'checked' : '' ?> style="width: auto;">
          <span>Tandai sebagai Indikator Kritis (misal: dorongan melukai diri sendiri / krisis darurat)</span>
        </label>
      </div>

      <?php if (!$editQ): ?>
        <p class="muted" style="font-size: 0.825rem;">
          <i class="fa-solid fa-circle-info text-primary"></i> 5 pilihan skala penilaian (Tidak pernah=0 s/d Sangat sering=4) akan dibuat secara otomatis.
        </p>
      <?php endif; ?>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalFormQ').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Pertanyaan</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
