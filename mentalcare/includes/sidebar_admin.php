<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);
$pageTitle = $pageTitle ?? 'Dashboard Admin';
$activeMenu = $activeMenu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> - Administrator | <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <!-- Chart.js untuk Visualisasi Grafik Statistik -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="app-layout">
  <!-- Sidebar Administrator -->
  <aside class="app-sidebar">
    <div class="sidebar-brand">
      <a href="<?= url('index.php') ?>" class="brand-logo">
        <i class="fa-solid fa-leaf"></i>
        <span><?= APP_NAME ?></span>
      </a>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar" style="background:#fee2e2; color:#dc2626;">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <div class="user-info">
        <div class="name"><?= e($u['nama']) ?></div>
        <div class="role-badge" style="color:#dc2626;"><i class="fa-solid fa-crown"></i> Super Admin</div>
      </div>
    </div>

    <ul class="sidebar-nav">
      <li class="sidebar-nav-title">Ringkasan Sistem</li>
      <li>
        <a href="<?= url('admin/index.php') ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
          <i class="fa-solid fa-chart-line"></i>
          <span>Dashboard & Statistik</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Manajemen Pengguna</li>
      <li>
        <a href="<?= url('admin/pasien.php') ?>" class="<?= $activeMenu === 'pasien' ? 'active' : '' ?>">
          <i class="fa-solid fa-hospital-user"></i>
          <span>Kelola Pasien</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/psikolog.php') ?>" class="<?= $activeMenu === 'psikolog' ? 'active' : '' ?>">
          <i class="fa-solid fa-user-doctor"></i>
          <span>Kelola Psikolog</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Manajemen Kuisioner</li>
      <li>
        <a href="<?= url('admin/kategori.php') ?>" class="<?= $activeMenu === 'kategori' ? 'active' : '' ?>">
          <i class="fa-solid fa-folder-tree"></i>
          <span>Kategori Assessment</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/pertanyaan.php') ?>" class="<?= $activeMenu === 'pertanyaan' ? 'active' : '' ?>">
          <i class="fa-solid fa-circle-question"></i>
          <span>Kelola Pertanyaan</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/aturan_skor.php') ?>" class="<?= $activeMenu === 'aturan_skor' ? 'active' : '' ?>">
          <i class="fa-solid fa-sliders"></i>
          <span>Aturan Rentang Skor</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Layanan & Rekap</li>
      <li>
        <a href="<?= url('admin/konsultasi.php') ?>" class="<?= $activeMenu === 'konsultasi' ? 'active' : '' ?>">
          <i class="fa-solid fa-calendar-check"></i>
          <span>Kelola Konsultasi</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/laporan.php') ?>" class="<?= $activeMenu === 'laporan' ? 'active' : '' ?>">
          <i class="fa-solid fa-file-invoice"></i>
          <span>Laporan & Cetak</span>
        </a>
      </li>
      <li>
        <a href="<?= url('admin/artikel.php') ?>" class="<?= $activeMenu === 'artikel' ? 'active' : '' ?>">
          <i class="fa-solid fa-newspaper"></i>
          <span>Kelola Artikel</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Pengaturan</li>
      <li>
        <a href="<?= url('admin/profil.php') ?>" class="<?= $activeMenu === 'profil' ? 'active' : '' ?>">
          <i class="fa-solid fa-gear"></i>
          <span>Profil Admin</span>
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
          <p>Panel Kontrol Sistem & Master Data</p>
        </div>
      </div>
      <div class="app-topbar-right">
        <span class="badge badge-danger"><i class="fa-solid fa-shield"></i> Hak Akses Admin</span>
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
