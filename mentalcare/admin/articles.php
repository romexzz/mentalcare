<?php
require '../config/config.php';
$me = require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah') {
        $j = trim($_POST['judul'] ?? ''); $i = trim($_POST['isi'] ?? '');
        if ($j && $i) { db()->prepare('INSERT INTO articles (judul,isi,kategori_id,penulis_id) VALUES (?,?,?,?)')->execute([$j,$i,(int)$_POST['kategori_id'] ?: null,$me['id']]); flash('Artikel ditambahkan.'); }
        else flash('Judul dan isi wajib diisi.', 'error');
    } elseif ($aksi === 'hapus') { db()->prepare('DELETE FROM articles WHERE id=?')->execute([(int)$_POST['id']]); flash('Artikel dihapus.'); }
    redirect('admin/articles.php');
}
$cats = db()->query('SELECT * FROM categories')->fetchAll();
$list = db()->query('SELECT a.id,a.judul,a.tanggal,c.nama kategori FROM articles a LEFT JOIN categories c ON c.id=a.kategori_id ORDER BY a.id DESC')->fetchAll();
$pageTitle='Kelola Artikel'; require '../includes/header.php';
?>
<h2>Kelola Artikel</h2>
<div class="card"><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <label>Judul</label><input name="judul" required maxlength="200">
  <label>Kategori</label><select name="kategori_id"><option value="0">Umum</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nama']) ?></option><?php endforeach; ?></select>
  <label>Isi</label><textarea name="isi" required></textarea><p><button>Tambah artikel</button></p></form></div>
<div class="card"><table><tr><th>Judul</th><th>Kategori</th><th>Tanggal</th><th></th></tr>
<?php foreach ($list as $a): ?><tr><td><?= e($a['judul']) ?></td><td><?= e($a['kategori'] ?? 'Umum') ?></td><td><?= e($a['tanggal']) ?></td>
<td><form method="post" onsubmit="return confirm('Hapus artikel?')"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="danger">Hapus</button></form></td></tr><?php endforeach; ?></table></div>
<?php require '../includes/footer.php'; ?>
