<?php
require_once __DIR__ . '/auth.php';
require_login();

$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();
$totalBaru = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();

// ===== FILTER TANGGAL =====
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-29 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
// Validasi ringan supaya query tidak dikasih tanggal ngawur
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');
$sampaiFull = $sampai . ' 23:59:59';
$dariFull = $dari . ' 00:00:00';

// ===== FILTER KATEGORI (opsional) =====
$kategoriList = $pdo->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();
$kategoriId = (int) ($_GET['kategori_id'] ?? 0);
$pakaiFilterKategori = $kategoriId > 0;
$namaKategoriTerpilih = '';
if ($pakaiFilterKategori) {
    foreach ($kategoriList as $k) {
        if ((int) $k['id'] === $kategoriId) { $namaKategoriTerpilih = $k['nama']; break; }
    }
    // Kalau id kategori yang dikirim ternyata tidak valid, anggap tidak ada filter.
    if ($namaKategoriTerpilih === '') { $kategoriId = 0; $pakaiFilterKategori = false; }
}

// ===== RINGKASAN =====
// Kalau filter kategori aktif, "total penjualan" dan "jumlah transaksi" dihitung
// dari item pesanan yang produknya termasuk kategori tersebut (bukan total pesanan
// utuh, karena satu pesanan bisa berisi produk dari beberapa kategori sekaligus).
if ($pakaiFilterKategori) {
    $stmtSum = $pdo->prepare("SELECT COUNT(DISTINCT t.id) AS jumlah_transaksi, COALESCE(SUM(ti.subtotal),0) AS total_omzet
        FROM transaksi_item ti
        JOIN transaksi t ON t.id = ti.transaksi_id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?");
    $stmtSum->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmtSum = $pdo->prepare("SELECT COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total),0) AS total_omzet
        FROM transaksi WHERE dibuat_pada BETWEEN ? AND ?");
    $stmtSum->execute([$dariFull, $sampaiFull]);
}
$ringkasan = $stmtSum->fetch();
$jumlahTransaksi = (int) $ringkasan['jumlah_transaksi'];
$totalOmzet = (float) $ringkasan['total_omzet'];
$rataRata = $jumlahTransaksi > 0 ? $totalOmzet / $jumlahTransaksi : 0;

if ($pakaiFilterKategori) {
    $stmtQty = $pdo->prepare("SELECT COALESCE(SUM(ti.jumlah),0) FROM transaksi_item ti
        JOIN transaksi t ON t.id = ti.transaksi_id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?");
    $stmtQty->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmtQty = $pdo->prepare("SELECT COALESCE(SUM(ti.jumlah),0) FROM transaksi_item ti
        JOIN transaksi t ON t.id = ti.transaksi_id WHERE t.dibuat_pada BETWEEN ? AND ?");
    $stmtQty->execute([$dariFull, $sampaiFull]);
}
$totalItemTerjual = (int) $stmtQty->fetchColumn();

// ===== GRAFIK OMZET PER HARI =====
if ($pakaiFilterKategori) {
    $stmtChart = $pdo->prepare("SELECT DATE(t.dibuat_pada) AS tanggal, SUM(ti.subtotal) AS omzet
        FROM transaksi_item ti
        JOIN transaksi t ON t.id = ti.transaksi_id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?
        GROUP BY DATE(t.dibuat_pada) ORDER BY tanggal");
    $stmtChart->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmtChart = $pdo->prepare("SELECT DATE(dibuat_pada) AS tanggal, SUM(total) AS omzet
        FROM transaksi WHERE dibuat_pada BETWEEN ? AND ?
        GROUP BY DATE(dibuat_pada) ORDER BY tanggal");
    $stmtChart->execute([$dariFull, $sampaiFull]);
}
$chartRaw = [];
foreach ($stmtChart->fetchAll() as $row) {
    $chartRaw[$row['tanggal']] = (float) $row['omzet'];
}
// Isi tanggal yang kosong (tidak ada transaksi) dengan 0, supaya grafiknya berkesinambungan.
$chartData = [];
$cursor = strtotime($dari);
$akhir = strtotime($sampai);
$jumlahHari = max(1, (int) round(($akhir - $cursor) / 86400) + 1);
while ($cursor <= $akhir) {
    $key = date('Y-m-d', $cursor);
    $chartData[$key] = $chartRaw[$key] ?? 0;
    $cursor = strtotime('+1 day', $cursor);
}
$maxOmzetHarian = max(1, ...array_values($chartData));
// Kalau rentang tanggal terlalu panjang, tampilkan sebagian titik saja biar label tidak numpuk.
$totalTitik = count($chartData);
$labelStep = max(1, (int) ceil($totalTitik / 12));

// ===== PRODUK TERLARIS =====
if ($pakaiFilterKategori) {
    $stmtTop = $pdo->prepare("SELECT ti.nama_produk, SUM(ti.jumlah) AS total_qty, SUM(ti.subtotal) AS total_omzet_produk
        FROM transaksi_item ti JOIN transaksi t ON t.id = ti.transaksi_id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?
        GROUP BY ti.nama_produk ORDER BY total_qty DESC LIMIT 5");
    $stmtTop->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmtTop = $pdo->prepare("SELECT ti.nama_produk, SUM(ti.jumlah) AS total_qty, SUM(ti.subtotal) AS total_omzet_produk
        FROM transaksi_item ti JOIN transaksi t ON t.id = ti.transaksi_id
        WHERE t.dibuat_pada BETWEEN ? AND ?
        GROUP BY ti.nama_produk ORDER BY total_qty DESC LIMIT 5");
    $stmtTop->execute([$dariFull, $sampaiFull]);
}
$produkTerlaris = $stmtTop->fetchAll();
$maxQtyTerlaris = max(1, ...array_column($produkTerlaris, 'total_qty') ?: [1]);

// ===== DAFTAR TRANSAKSI (dengan pagination) =====
// Kalau filter kategori aktif, daftar dibatasi ke pesanan yang mengandung
// minimal satu produk dari kategori tersebut.
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if ($pakaiFilterKategori) {
    $stmtCount = $pdo->prepare("SELECT COUNT(DISTINCT t.id) FROM transaksi t
        JOIN transaksi_item ti ON ti.transaksi_id = t.id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?");
    $stmtCount->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE dibuat_pada BETWEEN ? AND ?');
    $stmtCount->execute([$dariFull, $sampaiFull]);
}
$totalTransaksiFilter = (int) $stmtCount->fetchColumn();
$totalPages = max(1, (int) ceil($totalTransaksiFilter / $perPage));

if ($pakaiFilterKategori) {
    $stmtList = $pdo->prepare("SELECT DISTINCT t.*,
        (SELECT COALESCE(SUM(jumlah),0) FROM transaksi_item WHERE transaksi_id = t.id) AS total_item
        FROM transaksi t
        JOIN transaksi_item ti ON ti.transaksi_id = t.id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?
        ORDER BY t.dibuat_pada DESC LIMIT ? OFFSET ?");
    $stmtList->bindValue(1, $dariFull);
    $stmtList->bindValue(2, $sampaiFull);
    $stmtList->bindValue(3, $kategoriId, PDO::PARAM_INT);
    $stmtList->bindValue(4, $perPage, PDO::PARAM_INT);
    $stmtList->bindValue(5, $offset, PDO::PARAM_INT);
} else {
    $stmtList = $pdo->prepare("SELECT t.*,
        (SELECT COALESCE(SUM(jumlah),0) FROM transaksi_item WHERE transaksi_id = t.id) AS total_item
        FROM transaksi t WHERE t.dibuat_pada BETWEEN ? AND ?
        ORDER BY t.dibuat_pada DESC LIMIT ? OFFSET ?");
    $stmtList->bindValue(1, $dariFull);
    $stmtList->bindValue(2, $sampaiFull);
    $stmtList->bindValue(3, $perPage, PDO::PARAM_INT);
    $stmtList->bindValue(4, $offset, PDO::PARAM_INT);
}
$stmtList->execute();
$transaksiList = $stmtList->fetchAll();

$queryStringPage = http_build_query(['dari' => $dari, 'sampai' => $sampai, 'kategori_id' => $kategoriId ?: '']);
$queryStringCetak = http_build_query(['dari' => $dari, 'sampai' => $sampai, 'kategori_id' => $kategoriId ?: '']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan — Lalunaco</title>
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
      <a href="dashboard.php" class="admin-nav-item">
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
      <a href="laporan.php" class="admin-nav-item active">
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
    <div class="page-intro">
      <div>
        <h1 class="page-title">Laporan Penjualan</h1>
        <p class="page-subtitle">Ringkasan omzet, produk terlaris, dan riwayat transaksi.</p>
      </div>
    </div>

    <form method="get" action="laporan.php" class="laporan-filter">
      <label>Dari
        <input type="date" name="dari" value="<?= h($dari) ?>">
      </label>
      <label>Sampai
        <input type="date" name="sampai" value="<?= h($sampai) ?>">
      </label>
      <label>Kategori
        <select name="kategori_id">
          <option value="0">Semua kategori</option>
          <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $kategoriId === (int) $k['id'] ? 'selected' : '' ?>><?= h($k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit" class="btn btn-primary">Terapkan</button>
      <a href="laporan_export.php?<?= h($queryStringPage) ?>" class="icon-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        <span>Export CSV</span>
      </a>
      <a href="laporan_cetak.php?<?= h($queryStringCetak) ?>" target="_blank" class="icon-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak Laporan</span>
      </a>
    </form>
    <?php if ($pakaiFilterKategori): ?>
      <p class="checkout-note" style="margin-top:-0.8rem;margin-bottom:1rem;">Menampilkan data untuk kategori: <strong><?= h($namaKategoriTerpilih) ?></strong>. Total penjualan &amp; item terjual dihitung dari produk kategori ini saja.</p>
    <?php endif; ?>

    <div class="admin-stats">
      <div class="stat-card stat-card-inline">
        <span class="stat-card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </span>
        <span class="stat-card-text"><span class="stat-value"><?= format_rupiah($totalOmzet) ?></span><span class="stat-label">Total omzet</span></span>
      </div>
      <div class="stat-card stat-card-inline">
        <span class="stat-card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        </span>
        <span class="stat-card-text"><span class="stat-value"><?= $jumlahTransaksi ?></span><span class="stat-label">Transaksi</span></span>
      </div>
      <div class="stat-card stat-card-inline">
        <span class="stat-card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.82 0l4.6-4.6a2 2 0 0 0 0-2.82z"></path></svg>
        </span>
        <span class="stat-card-text"><span class="stat-value"><?= $totalItemTerjual ?></span><span class="stat-label">Item terjual</span></span>
      </div>
      <div class="stat-card stat-card-inline">
        <span class="stat-card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </span>
        <span class="stat-card-text"><span class="stat-value"><?= format_rupiah($rataRata) ?></span><span class="stat-label">Rata-rata/transaksi</span></span>
      </div>
    </div>

    <div class="laporan-main-grid">
      <div class="laporan-chart-card">
        <h2 class="pesanan-detail-subheading">Omzet Harian</h2>
        <?php if ($totalOmzet <= 0): ?>
          <p class="empty-state">Belum ada transaksi di rentang tanggal ini.</p>
        <?php else: ?>
          <?php
            // Sumbu Y dibulatkan ke atas supaya garis bantu angkanya "bulat".
            $mag = 10 ** floor(log10($maxOmzetHarian));
            $langkah = $mag / 2;
            $axisMax = max($langkah, ceil($maxOmzetHarian / $langkah) * $langkah);
            $hariTertinggi = array_search($maxOmzetHarian, $chartData);
            $ringkas = function ($n) {
                if ($n >= 1000000) return 'Rp' . rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . ' jt';
                if ($n >= 1000) return 'Rp' . round($n / 1000) . ' rb';
                return 'Rp' . round($n);
            };
          ?>
          <div class="omzet-wrap">
            <div class="omzet-y">
              <span><?= h($ringkas($axisMax)) ?></span>
              <span><?= h($ringkas($axisMax / 2)) ?></span>
              <span>0</span>
            </div>
            <div class="omzet-body">
              <div class="omzet-chart" style="--n:<?= $totalTitik ?>">
                <?php foreach ($chartData as $tgl => $omzet): $tinggi = $omzet > 0 ? max(2, ($omzet / $axisMax) * 100) : 0; ?>
                  <div class="omzet-col" title="<?= h(date('d M Y', strtotime($tgl))) ?>: <?= format_rupiah($omzet) ?>">
                    <div class="omzet-bar<?= $tgl === $hariTertinggi ? ' is-top' : '' ?>" style="height:<?= round($tinggi, 1) ?>%"></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="omzet-labels" style="--n:<?= $totalTitik ?>">
                <?php $i = 0; foreach ($chartData as $tgl => $omzet): ?>
                  <span><?php if ($i % $labelStep === 0): ?><em><?= h(date('d/m', strtotime($tgl))) ?></em><?php endif; ?></span>
                <?php $i++; endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="pesanan-side-card">
        <h2 class="pesanan-detail-subheading">Produk Terlaris</h2>
        <?php if (empty($produkTerlaris)): ?>
          <p class="empty-state">Belum ada data.</p>
        <?php else: ?>
          <div class="laporan-top-list">
            <?php foreach ($produkTerlaris as $p): ?>
              <div class="laporan-top-item">
                <div class="laporan-top-head">
                  <span class="laporan-top-nama"><?= h($p['nama_produk']) ?></span>
                  <span class="laporan-top-qty"><?= (int) $p['total_qty'] ?> pcs</span>
                </div>
                <div class="laporan-top-bar-track">
                  <div class="laporan-top-bar-fill" style="width:<?= max(6, round(($p['total_qty'] / $maxQtyTerlaris) * 100)) ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <h2 class="pesanan-detail-subheading" style="margin-top:1.6rem;">Riwayat Transaksi</h2>
    <div class="table-scroll">
      <table class="admin-table">
        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
        <tbody>
          <?php foreach ($transaksiList as $t): ?>
          <tr>
            <td><a href="pesanan_detail.php?id=<?= $t['id'] ?>"><strong><?= h($t['kode']) ?></strong></a></td>
            <td><?= h($t['nama']) ?></td>
            <td><?= (int) $t['total_item'] ?> pcs</td>
            <td><?= format_rupiah($t['total']) ?></td>
            <td><?= h(ucwords($t['status'] ?: 'menunggu konfirmasi')) ?></td>
            <td><?= h(date('d M Y, H:i', strtotime($t['dibuat_pada']))) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($transaksiList)): ?>
            <tr><td colspan="6">Tidak ada transaksi di rentang tanggal ini.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan <?= $totalTransaksiFilter ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalTransaksiFilter) ?> dari <?= $totalTransaksiFilter ?> transaksi</span>
      <div class="pagination-buttons">
        <a href="?<?= h($queryStringPage) ?>&page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">&#8249;</a>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?<?= h($queryStringPage) ?>&page=<?= $p ?>" class="pagination-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a href="?<?= h($queryStringPage) ?>&page=<?= min($totalPages, $page + 1) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">&#8250;</a>
      </div>
    </div>
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