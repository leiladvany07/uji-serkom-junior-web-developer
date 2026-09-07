<?php
require_once __DIR__ . '/auth.php';
require_login();

$produkList = $pdo->query('SELECT produk.*, kategori.nama AS kategori_nama
                            FROM produk JOIN kategori ON produk.kategori_id = kategori.id
                            ORDER BY produk.dibuat_pada DESC')->fetchAll();

$totalPesan = $pdo->query('SELECT COUNT(*) FROM pesan')->fetchColumn();
$totalProduk = count($produkList);
$totalStok = array_sum(array_column($produkList, 'stok'));
$totalBaru = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
      <p class="logo">Laluna<span>co.</span></p>
      <p class="admin-sidebar-tagline">handmade knitwear</p>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="admin-nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        <span>Produk</span>
      </a>
      <a href="pesan.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        <span>Pesan masuk</span>
        <?php if ($totalBaru > 0): ?><span class="admin-nav-badge"><?= $totalBaru ?></span><?php endif; ?>
      </a>
      <a href="../index.php" target="_blank" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <span>Lihat toko</span>
      </a>
      <a href="logout.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Keluar</span>
      </a>
    </nav>
    <p class="admin-sidebar-footer">Handmade with love &hearts;</p>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <h1>Produk</h1>
      <a href="produk_form.php" class="btn btn-primary">Tambah produk</a>
    </div>

    <div class="admin-stats">
      <div class="stat-card"><span class="stat-value"><?= $totalProduk ?></span><span class="stat-label">Total produk</span></div>
      <div class="stat-card"><span class="stat-value"><?= $totalStok ?></span><span class="stat-label">Total stok</span></div>
      <div class="stat-card"><span class="stat-value"><?= (int) $totalPesan ?></span><span class="stat-label">Pesan masuk</span></div>
    </div>

    <table class="admin-table">
      <thead>
        <tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($produkList as $p): ?>
        <tr>
          <td><?= h($p['nama']) ?></td>
          <td><?= h($p['kategori_nama']) ?></td>
          <td><?= format_rupiah($p['harga']) ?></td>
          <td><?= (int) $p['stok'] ?></td>
          <td class="admin-actions">
            <a href="produk_form.php?id=<?= $p['id'] ?>">Ubah</a>
            <form method="post" action="produk_hapus.php" onsubmit="return confirm('Hapus produk ini?');" style="display:inline">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button type="submit" class="link-danger">Hapus</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($produkList)): ?>
        <tr><td colspan="5">Belum ada produk.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>
</body>
</html>