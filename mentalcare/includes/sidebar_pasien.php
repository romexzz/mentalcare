<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);
$pageTitle = $pageTitle ?? 'Dashboard Pasien';
$activeMenu = $activeMenu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<div class="app-layout">
  <!-- Sidebar Pasien -->
  <aside class="app-sidebar">
    <div class="sidebar-brand">
      <a href="<?= url('index.php') ?>" class="brand-logo">
        <i class="fa-solid fa-leaf"></i>
        <span><?= APP_NAME ?></span>
      </a>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar">
        <i class="fa-solid fa-user"></i>
      </div>
      <div class="user-info">
        <div class="name"><?= e($u['nama']) ?></div>
        <div class="role-badge"><i class="fa-solid fa-heart-pulse"></i> Pasien</div>
      </div>
    </div>

    <ul class="sidebar-nav">
      <li class="sidebar-nav-title">Menu Utama</li>
      <li>
        <a href="<?= url('pasien/index.php') ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
          <i class="fa-solid fa-house"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li>
        <a href="<?= url('pasien/assessment.php') ?>" class="<?= $activeMenu === 'assessment' ? 'active' : '' ?>">
          <i class="fa-solid fa-clipboard-check"></i>
          <span>Mulai Pemeriksaan</span>
        </a>
      </li>
      <li>
        <a href="<?= url('pasien/riwayat.php') ?>" class="<?= $activeMenu === 'riwayat' ? 'active' : '' ?>">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Riwayat Pemeriksaan</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Konsultasi</li>
      <li>
        <a href="<?= url('pasien/psikolog.php') ?>" class="<?= $activeMenu === 'psikolog' ? 'active' : '' ?>">
          <i class="fa-solid fa-user-doctor"></i>
          <span>Daftar Psikolog</span>
        </a>
      </li>
      <li>
        <a href="<?= url('pasien/konsultasi_riwayat.php') ?>" class="<?= $activeMenu === 'konsultasi' ? 'active' : '' ?>">
          <i class="fa-solid fa-comments"></i>
          <span>Jadwal & Konsultasi</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Pengaturan Akun</li>
      <li>
        <a href="<?= url('pasien/profil.php') ?>" class="<?= $activeMenu === 'profil' ? 'active' : '' ?>">
          <i class="fa-solid fa-id-card"></i>
          <span>Profil Saya</span>
        </a>
      </li>
      <li>
        <a href="<?= url('index.php') ?>">
          <i class="fa-solid fa-globe"></i>
          <span>Lihat Website</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-footer">
      <a href="<?= url('logout.php') ?>" class="btn btn-outline btn-block btn-sm">
        <i class="fa-solid fa-right-from-bracket"></i> Keluar
      </a>
    </div>
  </aside>

  <!-- Konten Utama -->
  <div class="app-main">
    <header class="app-topbar">
      <div class="app-topbar-left">
        <button class="sidebar-toggle-btn" aria-label="Toggle sidebar">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="page-header-title">
          <h2><?= e($pageTitle) ?></h2>
          <p><?= APP_TAGLINE ?></p>
        </div>
      </div>
      <div class="app-topbar-right">
        <a href="<?= url('pasien/profil.php') ?>" class="btn btn-light btn-sm">
          <i class="fa-solid fa-user"></i> <?= e($u['nama']) ?>
        </a>
      </div>
    </header>

    <div class="app-body">
      <!-- Flash Message -->
      <?php if ($flash = flash()): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>">
          <i class="fa-solid fa-circle-info"></i>
          <div><?= e($flash['message']) ?></div>
        </div>
      <?php endif; ?>
