<?php if (!isset($pdo)) { require_once __DIR__ . '/../config.php'; } ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' — Lalunaco' : 'Lalunaco — Toko Rajut Tangan' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= time() ?>">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a href="<?= BASE_URL ?>/index.php" class="logo">Laluna<span>co.</span></a>

    <button class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-expanded="false">
      <i></i><i></i><i></i>
    </button>

    <nav class="main-nav" id="mainNav">
      <a href="<?= BASE_URL ?>/index.php">Beranda</a>
      <?php $kategoriNav = $pdo->query('SELECT id, nama, slug FROM kategori ORDER BY nama')->fetchAll(); ?>
      <a href="<?= BASE_URL ?>/produk.php">Produk</a>

      <div class="nav-dropdown">
        <a href="<?= BASE_URL ?>/tentang.php" class="nav-dropdown-trigger">Tentang</a>
      </div>

      <a href="<?= BASE_URL ?>/kontak.php">Kontak</a>
      <a href="<?= BASE_URL ?>/profil-statis/" class="nav-profile">Profil developer</a>
      <a href="<?= BASE_URL ?>/admin/login.php" class="nav-admin" title="Masuk admin" aria-label="Masuk admin">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"></path></svg>
      </a>
    </nav>
  </div>
</header>

<main class="site-main">