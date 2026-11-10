<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Artikel Edukasi';
$activeMenu = 'artikel';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Simpan Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $judul = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? 'Edukasi');
        $ringkasan = trim($_POST['ringkasan'] ?? '');
        $isi = trim($_POST['isi'] ?? '');

        // Generate slug
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($judul));
        $slug = trim($slug, '-');

        if (!$judul || !$isi) {
            flash('Judul dan isi artikel tidak boleh kosong.', 'error');
        } else {
            if ($id > 0) {
                // Update
                $stmtSlug = db()->prepare('SELECT id FROM artikel WHERE slug = ? AND id != ?');
                $stmtSlug->execute([$slug, $id]);
                if ($stmtSlug->fetch()) $slug .= '-' . time();

                db()->prepare('
                    UPDATE artikel 
                    SET judul = ?, slug = ?, kategori = ?, ringkasan = ?, isi = ?
                    WHERE id = ?
                ')->execute([$judul, $slug, $kategori, $ringkasan, $isi, $id]);

                flash('Artikel berhasil diperbarui.', 'success');
                redirect('admin/artikel.php');
            } else {
                // Tambah baru
                $stmtSlug = db()->prepare('SELECT id FROM artikel WHERE slug = ?');
                $stmtSlug->execute([$slug]);
                if ($stmtSlug->fetch()) $slug .= '-' . time();

                db()->prepare('
                    INSERT INTO artikel (judul, slug, kategori, ringkasan, isi, penulis_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ')->execute([$judul, $slug, $kategori, $ringkasan, $isi, $u['id']]);

                flash('Artikel edukasi baru berhasil diterbitkan.', 'success');
                redirect('admin/artikel.php');
            }
        }
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM artikel WHERE id = ?')->execute([$id]);
        flash('Artikel telah dihapus.', 'info');
        redirect('admin/artikel.php');
    }
}

// Data edit
$editArticle = null;
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM artikel WHERE id = ?');
    $stmt->execute([$id]);
    $editArticle = $stmt->fetch();
}

// Ambil list artikel
$articles = db()->query('
    SELECT a.*, u.nama as nama_penulis 
    FROM artikel a 
    LEFT JOIN users u ON u.id = a.penulis_id 
    ORDER BY a.created_at DESC
')->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Manajemen Artikel Edukasi Mental</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Kelola publikasi psikoedukasi, panduan relaksasi, dan artikel kesehatan mental.
      </p>
    </div>
    <div>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalFormArtikel').style.display='block'">
        <i class="fa-solid fa-plus"></i> Tulis Artikel Baru
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Judul Artikel</th>
          <th>Kategori</th>
          <th>Penulis</th>
          <th>Tanggal Terbit</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($articles)): ?>
          <?php foreach ($articles as $idx => $art): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td>
                <strong style="color: var(--primary-dark); font-size: 0.95rem;">
                  <a href="<?= url('artikel_detail.php?id=' . $art['id']) ?>" target="_blank"><?= e($art['judul']) ?></a>
                </strong>
                <div class="muted" style="font-size: 0.8rem;"><?= e(mb_strimwidth($art['ringkasan'], 0, 75, '...')) ?></div>
              </td>
              <td><span class="badge badge-info"><?= e($art['kategori']) ?></span></td>
              <td><?= e($art['nama_penulis'] ?? 'Admin') ?></td>
              <td><?= format_tanggal($art['created_at']) ?></td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= url('admin/artikel.php?action=edit&id=' . $art['id']) ?>" class="btn btn-light btn-sm" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>

                  <form method="post" action="" onsubmit="return confirm('Hapus artikel ini?');" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $art['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger)!important; border-color: var(--danger);" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center muted" style="padding: 24px;">Belum ada artikel yang dipublikasikan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah / Edit Artikel -->
<div id="modalFormArtikel" style="display: <?= $editArticle ? 'block' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 750px; margin: 40px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;"><?= $editArticle ? 'Edit Artikel Edukasi' : 'Tulis Artikel Edukasi Baru' ?></h3>
      <button type="button" onclick="document.getElementById('modalFormArtikel').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int)($editArticle['id'] ?? 0) ?>">

      <div class="form-row">
        <div class="form-group" style="flex: 2;">
          <label class="form-label">Judul Artikel <span class="text-danger">*</span></label>
          <input type="text" name="judul" class="form-control" required placeholder="Contoh: 5 Cara Efektif Mengatasi Kecemasan Akut" value="<?= e($editArticle['judul'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Kategori <span class="text-danger">*</span></label>
          <input type="text" name="kategori" class="form-control" required placeholder="Stres / Relaksasi / Burnout" value="<?= e($editArticle['kategori'] ?? 'Edukasi') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Ringkasan Singkat</label>
        <textarea name="ringkasan" class="form-control" rows="2" placeholder="Cuplikan 1-2 kalimat untuk preview kartu artikel..."><?= e($editArticle['ringkasan'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Isi Lengkap Artikel (Mendukung HTML dasar) <span class="text-danger">*</span></label>
        <textarea name="isi" class="form-control" rows="10" required placeholder="Tuliskan isi artikel lengkap..."><?= e($editArticle['isi'] ?? '') ?></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalFormArtikel').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Publikasikan Artikel</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
