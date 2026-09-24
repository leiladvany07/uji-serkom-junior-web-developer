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
$totalPesanan = (int) $pdo->query('SELECT COUNT(*) FROM transaksi')->fetchColumn();
$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();
$totalOmzet = (float) $pdo->query('SELECT COALESCE(SUM(total),0) FROM transaksi')->fetchColumn();

// ===== Grafik omzet 7 hari terakhir (ringkasan cepat, detail lengkap ada di Laporan) =====
$dariGrafik = date('Y-m-d', strtotime('-6 days'));
$sampaiGrafik = date('Y-m-d');
$stmtGrafik = $pdo->prepare("SELECT DATE(dibuat_pada) AS tanggal, SUM(total) AS omzet
    FROM transaksi WHERE dibuat_pada BETWEEN ? AND ?
    GROUP BY DATE(dibuat_pada) ORDER BY tanggal");
$stmtGrafik->execute([$dariGrafik . ' 00:00:00', $sampaiGrafik . ' 23:59:59']);
$grafikRaw = [];
foreach ($stmtGrafik->fetchAll() as $row) { $grafikRaw[$row['tanggal']] = (float) $row['omzet']; }
$grafikData = [];
$cursorGrafik = strtotime($dariGrafik);
$akhirGrafik = strtotime($sampaiGrafik);
while ($cursorGrafik <= $akhirGrafik) {
    $key = date('Y-m-d', $cursorGrafik);
    $grafikData[$key] = $grafikRaw[$key] ?? 0;
    $cursorGrafik = strtotime('+1 day', $cursorGrafik);
}
$maxOmzetGrafik = max(1, ...array_values($grafikData));
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
  <a href="kategori.php" class="admin-nav-item">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.82 0l4.6-4.6a2 2 0 0 0 0-2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
    <span>Kategori</span>
  </a>
  <a href="pesanan.php" class="admin-nav-item">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
    <span>Pesanan</span>
    <?php if ($totalBaruPesanan > 0): ?><span class="admin-nav-badge"><?= $totalBaruPesanan ?></span><?php endif; ?>
  </a>
  <a href="laporan.php" class="admin-nav-item">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
    <span>Laporan</span>
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
  <a href="logout.php" class="admin-nav-item" id="logoutLink">
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
      <div class="stat-card"><span class="stat-value"><?= $totalPesanan ?></span><span class="stat-label">Pesanan masuk</span></div>
      <div class="stat-card"><span class="stat-value"><?= format_rupiah($totalOmzet) ?></span><span class="stat-label">Total omzet</span></div>
      <div class="stat-card"><span class="stat-value"><?= (int) $totalPesan ?></span><span class="stat-label">Pesan masuk</span></div>
    </div>

    <div class="laporan-chart-card" style="margin-bottom:1.6rem;">
      <h2 class="pesanan-detail-subheading">Omzet 7 Hari Terakhir</h2>
      <?php if ($totalOmzet <= 0): ?>
        <p class="empty-state">Belum ada transaksi.</p>
      <?php else: ?>
        <div class="laporan-chart">
          <?php foreach ($grafikData as $tgl => $omzet): $tinggi = $omzet > 0 ? max(4, round(($omzet / $maxOmzetGrafik) * 100)) : 2; ?>
            <div class="laporan-bar-wrap" title="<?= h(date('d M', strtotime($tgl))) ?>: <?= format_rupiah($omzet) ?>">
              <div class="laporan-bar" style="height:<?= $tinggi ?>%"></div>
              <span class="laporan-bar-label"><?= h(date('d/m', strtotime($tgl))) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <p class="checkout-note" style="margin-top:0.6rem;"><a href="laporan.php" style="color:var(--clay);font-weight:700;">Lihat laporan penjualan lengkap &rarr;</a></p>
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
<div class="confirm-overlay" id="confirmLogout">
  <div class="confirm-box">
    <h3>Keluar dari akun admin?</h3>
    <p>Kamu perlu login lagi buat masuk ke dashboard ini.</p>
    <div class="confirm-box-actions">
      <button type="button" class="confirm-btn-cancel" id="confirmLogoutCancel">Batal</button>
      <button type="button" class="confirm-btn-ok" id="confirmLogoutOk">Ya, Keluar</button>
    </div>
  </div>
</div>
<script>
(function(){
  var link = document.getElementById("logoutLink");
  var overlay = document.getElementById("confirmLogout");
  if (!link || !overlay) return;
  link.addEventListener("click", function(e){
    e.preventDefault();
    overlay.classList.add("open");
  });
  document.getElementById("confirmLogoutCancel").addEventListener("click", function(){
    overlay.classList.remove("open");
  });
  document.getElementById("confirmLogoutOk").addEventListener("click", function(){
    window.location.href = "logout.php";
  });
  overlay.addEventListener("click", function(e){
    if (e.target === overlay) overlay.classList.remove("open");
  });
})();
</script>
</body>
</html>