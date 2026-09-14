<?php
require_once __DIR__ . '/auth.php';
require_login();

$produkList = $pdo->query('SELECT produk.*, kategori.nama AS kategori_nama
                            FROM produk JOIN kategori ON produk.kategori_id = kategori.id
                            ORDER BY produk.dibuat_pada DESC')->fetchAll();

$totalPesan = $pdo->query('SELECT COUNT(*) FROM pesan')->fetchColumn();
$totalProduk = count($produkList);
$totalStok = array_sum(array_column($produkList, 'stok'));
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
    <p class="logo">Laluna<span>co.</span></p>
    <nav class="admin-nav">
      <a href="dashboard.php" class="active">Produk</a>
      <a href="pesan.php">Pesan masuk (<?= (int) $totalPesan ?>)</a>
      <a href="../index.php" target="_blank">Lihat toko</a>
      <a href="logout.php">Keluar</a>
    </nav>
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
