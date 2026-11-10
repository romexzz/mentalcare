<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);
$pageTitle = $pageTitle ?? 'Dashboard Psikolog';
$activeMenu = $activeMenu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> - Panel Psikolog | <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<div class="app-layout">
  <!-- Sidebar Psikolog -->
  <aside class="app-sidebar">
    <div class="sidebar-brand">
      <a href="<?= url('index.php') ?>" class="brand-logo">
        <i class="fa-solid fa-leaf"></i>
        <span><?= APP_NAME ?></span>
      </a>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar" style="background:#e0f2fe; color:#0284c7;">
        <i class="fa-solid fa-user-doctor"></i>
      </div>
      <div class="user-info">
        <div class="name"><?= e($u['nama']) ?></div>
        <div class="role-badge" style="color:#0284c7;"><i class="fa-solid fa-certificate"></i> Psikolog Profesional</div>
      </div>
    </div>

    <ul class="sidebar-nav">
      <li class="sidebar-nav-title">Praktik & Layanan</li>
      <li>
        <a href="<?= url('psikolog/index.php') ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
          <i class="fa-solid fa-gauge-high"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li>
        <a href="<?= url('psikolog/konsultasi.php') ?>" class="<?= $activeMenu === 'konsultasi' ? 'active' : '' ?>">
          <i class="fa-solid fa-calendar-check"></i>
          <span>Manajemen Konsultasi</span>
        </a>
      </li>
      <li>
        <a href="<?= url('psikolog/jadwal.php') ?>" class="<?= $activeMenu === 'jadwal' ? 'active' : '' ?>">
          <i class="fa-solid fa-calendar-days"></i>
          <span>Jadwal Praktik</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Data Pasien</li>
      <li>
        <a href="<?= url('psikolog/pasien.php') ?>" class="<?= $activeMenu === 'pasien' ? 'active' : '' ?>">
          <i class="fa-solid fa-users"></i>
          <span>Daftar Pasien & Riwayat</span>
        </a>
      </li>

      <li class="sidebar-nav-title">Pengaturan Akun</li>
      <li>
        <a href="<?= url('psikolog/profil.php') ?>" class="<?= $activeMenu === 'profil' ? 'active' : '' ?>">
          <i class="fa-solid fa-user-gear"></i>
          <span>Profil & Kredensial</span>
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
          <p>Portal Praktik Konseling & Psikologi Klinis</p>
        </div>
      </div>
      <div class="app-topbar-right">
        <span class="badge badge-info"><i class="fa-solid fa-user-doctor"></i> Psikolog Terdaftar</span>
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
