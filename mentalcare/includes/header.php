<?php
require_once __DIR__ . '/../config/config.php';
$u = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
$currentNav = $currentNav ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
  
  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Main Modern Stylesheet -->
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<!-- Header Navigasi Publik -->
<header class="public-header">
  <div class="container public-nav-wrapper">
    <a href="<?= url('index.php') ?>" class="brand-logo">
      <i class="fa-solid fa-leaf"></i>
      <span><?= APP_NAME ?></span>
    </a>

    <button class="menu-toggle" aria-label="Toggle menu">
      <i class="fa-solid fa-bars"></i>
    </button>

    <ul class="nav-links">
      <li><a href="<?= url('index.php') ?>" class="<?= $currentNav === 'home' ? 'active' : '' ?>">Beranda</a></li>
      <li><a href="<?= url('tentang.php') ?>" class="<?= $currentNav === 'tentang' ? 'active' : '' ?>">Tentang Kami</a></li>
      <li><a href="<?= url('layanan.php') ?>" class="<?= $currentNav === 'layanan' ? 'active' : '' ?>">Layanan</a></li>
      <li><a href="<?= url('artikel.php') ?>" class="<?= $currentNav === 'artikel' ? 'active' : '' ?>">Informasi & Edukasi</a></li>
      <li><a href="<?= url('kontak.php') ?>" class="<?= $currentNav === 'kontak' ? 'active' : '' ?>">Kontak</a></li>
    </ul>

    <div class="nav-cta">
      <?php if ($u): ?>
        <?php 
          $dashUrl = 'pasien/index.php';
          if ($u['role'] === 'admin') $dashUrl = 'admin/index.php';
          elseif ($u['role'] === 'psikolog') $dashUrl = 'psikolog/index.php';
        ?>
        <a href="<?= url($dashUrl) ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-gauge"></i> Dashboard (<?= e($u['nama']) ?>)
        </a>
        <a href="<?= url('logout.php') ?>" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk
        </a>
        <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-user-plus"></i> Daftar
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Flash Notification Banner -->
<?php if ($flash = flash()): ?>
  <div class="container" style="margin-top: 20px;">
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>">
      <i class="fa-solid fa-circle-info"></i>
      <div><?= e($flash['message']) ?></div>
    </div>
  </div>
<?php endif; ?>
