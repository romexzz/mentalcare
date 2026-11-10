<?php
require_once 'config/config.php';
$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT a.*, u.nama as nama_penulis FROM artikel a LEFT JOIN users u ON u.id = a.penulis_id WHERE a.id = ?');
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    flash('Artikel tidak ditemukan.', 'warning');
    redirect('artikel.php');
}

$pageTitle = $article['judul'];
$currentNav = 'artikel';

// Ambil artikel terkait lainnya
$stmtOther = db()->prepare('SELECT id, judul, kategori FROM artikel WHERE id != ? ORDER BY id DESC LIMIT 4');
$stmtOther->execute([$id]);
$otherArticles = $stmtOther->fetchAll();

require 'includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 40px; margin-bottom: 60px;">
  <a href="<?= url('artikel.php') ?>" class="btn btn-light btn-sm" style="margin-bottom: 20px;">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Semua Artikel
  </a>

  <article class="card" style="padding: 36px;">
    <span class="badge badge-info" style="margin-bottom: 12px;"><?= e($article['kategori']) ?></span>
    <h1 style="font-size: 2rem; margin-bottom: 16px;"><?= e($article['judul']) ?></h1>
    
    <div style="display: flex; gap: 16px; color: var(--text-muted); font-size: 0.875rem; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 24px; flex-wrap: wrap;">
      <span><i class="fa-solid fa-user-pen text-primary"></i> Penulis: <strong><?= e($article['nama_penulis'] ?? 'Tim Psikolog MentalCare') ?></strong></span>
      <span><i class="fa-regular fa-calendar text-primary"></i> <?= format_tanggal($article['created_at']) ?></span>
    </div>

    <?php if (!empty($article['ringkasan'])): ?>
      <div style="background: var(--primary-light); padding: 16px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; font-style: italic; color: var(--primary-dark); border-left: 4px solid var(--primary);">
        "<?= e($article['ringkasan']) ?>"
      </div>
    <?php endif; ?>

    <div class="article-content" style="font-size: 1.05rem; line-height: 1.8; color: var(--text-main);">
      <?= $article['isi'] /* Isi artikel mengandung tag aman HTML seperti p, h3, ul, ol, li */ ?>
    </div>

    <div style="margin-top: 40px; padding: 24px; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md);">
      <h4 style="color: #92400e; margin-bottom: 8px;"><i class="fa-solid fa-triangle-exclamation"></i> Catatan Penting</h4>
      <p style="font-size: 0.9rem; color: #92400e; margin: 0;">
        Informasi dalam artikel ini bersifat psikoedukasi dan tidak menggantikan sesi diagnosis langsung atau penanganan klinis oleh psikolog atau psikiater.
      </p>
    </div>
  </article>

  <!-- Artikel Terkait -->
  <?php if (!empty($otherArticles)): ?>
    <div class="card" style="padding: 24px; margin-top: 24px;">
      <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-newspaper text-primary"></i> Artikel Lainnya</h3>
      <div class="grid grid-2">
        <?php foreach ($otherArticles as $oa): ?>
          <div style="padding: 10px; border-bottom: 1px solid var(--border-light);">
            <span class="badge badge-secondary" style="font-size: 0.75rem;"><?= e($oa['kategori']) ?></span>
            <div style="font-weight: 600; margin-top: 6px;">
              <a href="<?= url('artikel_detail.php?id=' . $oa['id']) ?>"><?= e($oa['judul']) ?></a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
