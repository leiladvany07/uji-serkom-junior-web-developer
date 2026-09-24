<?php
require_once __DIR__ . '/auth.php';
require_login();

// ===== Ambil filter (sama seperti laporan.php) =====
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-29 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');
$dariFull = $dari . ' 00:00:00';
$sampaiFull = $sampai . ' 23:59:59';

$kategoriId = (int) ($_GET['kategori_id'] ?? 0);
$pakaiFilterKategori = $kategoriId > 0;
$namaKategoriTerpilih = '';
if ($pakaiFilterKategori) {
    $stmtK = $pdo->prepare('SELECT nama FROM kategori WHERE id = ?');
    $stmtK->execute([$kategoriId]);
    $namaKategoriTerpilih = $stmtK->fetchColumn();
    if ($namaKategoriTerpilih === false) { $kategoriId = 0; $pakaiFilterKategori = false; $namaKategoriTerpilih = ''; }
}

// ===== Ringkasan =====
if ($pakaiFilterKategori) {
    $stmtSum = $pdo->prepare("SELECT COUNT(DISTINCT t.id) AS jumlah_transaksi, COALESCE(SUM(ti.subtotal),0) AS total_omzet
        FROM transaksi_item ti JOIN transaksi t ON t.id = ti.transaksi_id JOIN produk p ON p.id = ti.produk_id
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

// ===== Daftar transaksi lengkap (tanpa pagination, khusus untuk dicetak) =====
if ($pakaiFilterKategori) {
    $stmtList = $pdo->prepare("SELECT DISTINCT t.* FROM transaksi t
        JOIN transaksi_item ti ON ti.transaksi_id = t.id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?
        ORDER BY t.dibuat_pada ASC");
    $stmtList->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmtList = $pdo->prepare("SELECT * FROM transaksi WHERE dibuat_pada BETWEEN ? AND ? ORDER BY dibuat_pada ASC");
    $stmtList->execute([$dariFull, $sampaiFull]);
}
$transaksiList = $stmtList->fetchAll();

$periodeLabel = h(date('d M Y', strtotime($dari))) . ' – ' . h(date('d M Y', strtotime($sampai)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
  body{background:#EFE9DA;font-family:var(--font-body);}
  .cetak-wrap{max-width:820px;margin:2rem auto;padding:0 1rem 3rem;}
  .cetak-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}
  .cetak-toolbar a{font-size:0.88rem;color:var(--clay-dark);font-weight:700;}
  .cetak-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:2rem;}
  .cetak-head{text-align:center;border-bottom:2px solid var(--forest);padding-bottom:1rem;margin-bottom:1.2rem;}
  .cetak-brand{font-family:var(--font-display);font-size:1.4rem;font-weight:600;color:var(--forest);}
  .cetak-title{font-size:1rem;letter-spacing:0.05em;text-transform:uppercase;color:var(--ink);margin-top:0.2rem;}
  .cetak-meta{display:flex;flex-wrap:wrap;gap:0.4rem 2rem;font-size:0.9rem;margin-bottom:1.4rem;}
  .cetak-meta strong{color:var(--forest);}
  table.cetak-table{width:100%;border-collapse:collapse;margin-bottom:1.2rem;}
  table.cetak-table th{text-align:left;font-size:0.78rem;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--forest);padding:0.5rem 0.4rem;}
  table.cetak-table td{padding:0.5rem 0.4rem;border-bottom:1px solid #EFE6D2;font-size:0.88rem;}
  table.cetak-table td.num,table.cetak-table th.num{text-align:right;white-space:nowrap;}
  .cetak-total-row td{border-bottom:none;padding-top:0.8rem;font-weight:700;}
  .btn-print{background:var(--forest);color:#fff;border:none;padding:0.7rem 1.4rem;border-radius:6px;font-weight:700;font-size:0.9rem;cursor:pointer;}
  .btn-print:hover{background:var(--forest-2);}
  @media print{
    body{background:#fff;}
    .cetak-toolbar{display:none;}
    .cetak-wrap{margin:0;padding:0;max-width:none;}
    .cetak-card{border:none;border-radius:0;padding:0;}
  }
</style>
</head>
<body>
<div class="cetak-wrap">
  <div class="cetak-toolbar">
    <a href="laporan.php?<?= h(http_build_query(['dari' => $dari, 'sampai' => $sampai, 'kategori_id' => $kategoriId ?: ''])) ?>">&larr; Kembali ke laporan</a>
    <button type="button" class="btn-print" onclick="window.print()">🖨 Cetak</button>
  </div>

  <div class="cetak-card">
    <div class="cetak-head">
      <p class="cetak-brand">LALUNACO.</p>
      <p class="cetak-title">Laporan Penjualan</p>
    </div>

    <div class="cetak-meta">
      <span>Periode: <strong><?= $periodeLabel ?></strong></span>
      <?php if ($pakaiFilterKategori): ?><span>Kategori: <strong><?= h($namaKategoriTerpilih) ?></strong></span><?php endif; ?>
      <span>Total Transaksi: <strong><?= $jumlahTransaksi ?></strong></span>
      <span>Total Penjualan: <strong><?= format_rupiah($totalOmzet) ?></strong></span>
    </div>

    <table class="cetak-table">
      <thead>
        <tr><th>No</th><th>Kode Transaksi</th><th>Tanggal</th><th>Pembeli</th><th class="num">Total</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($transaksiList as $t): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= h($t['kode']) ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($t['dibuat_pada']))) ?></td>
          <td><?= h($t['nama']) ?></td>
          <td class="num"><?= format_rupiah($t['total']) ?></td>
          <td><?= h(ucwords($t['status'] ?: 'menunggu konfirmasi')) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($transaksiList)): ?>
          <tr><td colspan="6">Tidak ada transaksi pada periode ini.</td></tr>
        <?php endif; ?>
        <tr class="cetak-total-row">
          <td colspan="4" style="text-align:right;">Total Penjualan</td>
          <td class="num"><?= format_rupiah($totalOmzet) ?></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>