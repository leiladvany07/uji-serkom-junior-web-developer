<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pdo)) { require_once __DIR__ . '/../config.php'; }
?>
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

      <?php if (!empty($_SESSION['pelanggan_id'])): ?>
        <a href="<?= BASE_URL ?>/pesanan_saya.php" class="nav-account-link">Pesanan Saya</a>
        <a href="<?= BASE_URL ?>/logout.php" class="nav-account-link">Keluar</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="nav-account-link">Login/Daftar</a>
      <?php endif; ?>

      <?php $jumlahKeranjang = array_sum($_SESSION['keranjang'] ?? []); ?>
      <a href="<?= BASE_URL ?>/keranjang.php" class="nav-cart" title="Keranjang" aria-label="Keranjang">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <?php if ($jumlahKeranjang > 0): ?><span class="cart-badge"><?= $jumlahKeranjang ?></span><?php endif; ?>
      </a>

      <div class="main-nav-bottom">
        <a href="https://profil-statis-production-87dd.up.railway.app/" class="nav-profile">Profil developer</a>
        <a href="<?= BASE_URL ?>/admin/login.php" class="nav-admin" title="Masuk admin" aria-label="Masuk admin">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"></path></svg>
        </a>
      </div>
    </nav>
  </div>
</header>

<main class="site-main">