<?php
require_once __DIR__ . '/auth.php';
require_login();

$pesanList = $pdo->query('SELECT * FROM pesan ORDER BY dibuat_pada DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Masuk — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <p class="logo">Laluna<span>co.</span></p>
    <nav class="admin-nav">
      <a href="dashboard.php">Produk</a>
      <a href="pesan.php" class="active">Pesan masuk (<?= count($pesanList) ?>)</a>
      <a href="../index.php" target="_blank">Lihat toko</a>
      <a href="logout.php">Keluar</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <h1>Pesan masuk</h1>
    </div>

    <table class="admin-table">
      <thead>
        <tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Pesan</th><th>Tanggal</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pesanList as $p): ?>
        <tr>
          <td><?= h($p['nama']) ?></td>
          <td><?= h($p['email']) ?></td>
          <td><?= h($p['telepon'] ?: '-') ?></td>
          <td><?= h($p['isi_pesan']) ?></td>
          <td><?= h(date('d M Y, H:i', strtotime($p['dibuat_pada']))) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pesanList)): ?>
        <tr><td colspan="5">Belum ada pesan masuk.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>
</body>
</html>
