<?php
require_once 'config/config.php';
$pageTitle = 'Informasi & Edukasi Kesehatan Mental';
$currentNav = 'artikel';

$kategoriFilter = trim($_GET['kategori'] ?? '');
$query = 'SELECT a.*, u.nama as nama_penulis FROM artikel a LEFT JOIN users u ON u.id = a.penulis_id';
$params = [];

if ($kategoriFilter) {
    $query .= ' WHERE a.kategori = ?';
    $params[] = $kategoriFilter;
}
$query .= ' ORDER BY a.created_at DESC';

$stmt = db()->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Daftar kategori artikel yang tersedia
$categories = db()->query('SELECT DISTINCT kategori FROM artikel ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);

require 'includes/header.php';
?>

<div class="container" style="padding-top: 40px;">
  <div class="text-center" style="max-width: 700px; margin: 0 auto 30px;">
    <span class="badge badge-success" style="margin-bottom: 12px;">Edukasi & Psikoedukasi</span>
    <h1>Artikel Kesehatan Mental</h1>
    <p class="muted">
      Pelajari wawasan, tips relaksasi, dan pemahaman ilmiah seputar kesehatan mental untuk menemani perjalanan pemulihan Anda.
    </p>
  </div>

  <!-- Filter Kategori Kancing -->
  <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px;">
    <a href="<?= url('artikel.php') ?>" class="btn <?= empty($kategoriFilter) ? 'btn-primary' : 'btn-light' ?> btn-sm">
      Semua Kategori
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?= url('artikel.php?kategori=' . urlencode($cat)) ?>" class="btn <?= $kategoriFilter === $cat ? 'btn-primary' : 'btn-light' ?> btn-sm">
        <?= e($cat) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Daftar Kartu Artikel -->
  <div class="grid grid-3">
    <?php if (empty($articles)): ?>
      <div style="grid-column: 1 / -1;" class="card text-center">
        <p class="muted">Belum ada artikel pada kategori ini.</p>
      </div>
    <?php else: ?>
      <?php foreach ($articles as $art): ?>
        <div class="card" style="display: flex; flex-direction: column;">
          <span class="badge badge-info" style="width: fit-content; margin-bottom: 12px;"><?= e($art['kategori']) ?></span>
          <h3 style="font-size: 1.15rem; margin-bottom: 10px;">
            <a href="<?= url('artikel_detail.php?id=' . $art['id']) ?>"><?= e($art['judul']) ?></a>
          </h3>
          <p class="muted" style="font-size: 0.9rem; flex-grow: 1;"><?= e($art['ringkasan']) ?></p>
          <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; font-size: 0.825rem; color: var(--text-muted);">
            <span><i class="fa-solid fa-user-pen"></i> <?= e($art['nama_penulis'] ?? 'Tim MentalCare') ?></span>
            <span><i class="fa-regular fa-calendar"></i> <?= format_tanggal($art['created_at']) ?></span>
          </div>
          <div style="margin-top: 12px;">
            <a href="<?= url('artikel_detail.php?id=' . $art['id']) ?>" class="btn btn-outline btn-block btn-sm">
              Baca Selengkapnya
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
