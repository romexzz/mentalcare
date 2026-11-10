<?php
require_once 'config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Jurnal Pribadi Terenkripsi';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $j = trim($_POST['judul'] ?? '');
        $i = trim($_POST['isi'] ?? '');

        if ($j === '' || $i === '') {
            flash('Judul dan isi jurnal wajib diisi.', 'error');
        } else {
            db()->prepare('
                INSERT INTO journals (user_id, judul, isi_terenkripsi) 
                VALUES (?, ?, ?)
            ')->execute([$u['id'], mb_substr($j, 0, 150), encrypt_text($i)]);

            flash('Jurnal berhasil disimpan dan dienkripsi dengan aman.', 'success');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM journals WHERE id = ? AND user_id = ?')->execute([$id, $u['id']]);
        flash('Jurnal telah dihapus.', 'info');
    }
    redirect('journal.php');
}

$s = db()->prepare('SELECT * FROM journals WHERE user_id = ? ORDER BY id DESC');
$s->execute([$u['id']]);
$list = $s->fetchAll();

require 'includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 30px; margin-bottom: 60px;">
  <div style="margin-bottom: 24px;">
    <h2>📓 Jurnal Refleksi Pribadi</h2>
    <p class="muted">
      Tuliskan apa pun yang berkecamuk dalam pikiranmu. Catatan jurnal ini <strong>dienkripsi secara end-to-end (AES-256)</strong> dan hanya dapat dibaca oleh Anda.
    </p>
  </div>

  <div class="card" style="padding: 28px; margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px; font-size: 1.25rem;">
      <i class="fa-solid fa-pen-nib text-primary"></i> Tulis Jurnal Baru
    </h3>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah">

      <div class="form-group">
        <label class="form-label">Judul Jurnal / Refleksi</label>
        <input type="text" name="judul" class="form-control" required maxlength="150" placeholder="Contoh: Hal yang Membuatku Bersyukur Hari Ini...">
      </div>

      <div class="form-group">
        <label class="form-label">Isi Jurnal</label>
        <textarea name="isi" class="form-control" rows="5" required placeholder="Tumpahkan perasaan, kecemasan, atau harapanmu di sini tanpa takut dihakimi..."></textarea>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-lock"></i> Simpan & Enkripsi Jurnal
      </button>
    </form>
  </div>

  <div style="margin-bottom: 16px;">
    <h3><i class="fa-solid fa-book-open text-primary"></i> Catatan Jurnal Anda (<?= count($list) ?>)</h3>
  </div>

  <?php if (!empty($list)): ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($list as $j): ?>
        <div class="card" style="padding: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
            <h4 style="margin: 0; font-size: 1.2rem; color: var(--primary-dark);"><?= e($j['judul']) ?></h4>
            <span class="muted" style="font-size: 0.8rem;">
              <i class="fa-regular fa-calendar"></i> <?= format_tanggal($j['tanggal'], true) ?>
            </span>
          </div>

          <p style="font-size: 0.95rem; line-height: 1.7; color: var(--text-main); margin: 12px 0 16px; white-space: pre-wrap;"><?= e(decrypt_text($j['isi_terenkripsi'])) ?></p>

          <div style="text-align: right; border-top: 1px solid var(--border-light); padding-top: 10px;">
            <form method="post" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal ini?');" style="display: inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger)!important; border-color: var(--danger);">
                <i class="fa-solid fa-trash"></i> Hapus
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="card text-center" style="padding: 36px;">
      <i class="fa-solid fa-book" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"></i>
      <p class="muted">Belum ada catatan jurnal yang Anda buat. Mulai tulis jurnal pertamamu di atas!</p>
    </div>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
