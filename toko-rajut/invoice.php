<?php
require_once __DIR__ . '/config.php';

$kode = trim($_GET['kode'] ?? '');
if ($kode === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM transaksi WHERE kode = ?');
$stmt->execute([$kode]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    header('Location: index.php');
    exit;
}

$stmtItem = $pdo->prepare('SELECT * FROM transaksi_item WHERE transaksi_id = ? ORDER BY id');
$stmtItem->execute([$transaksi['id']]);
$itemList = $stmtItem->fetchAll();

$kembaliKe = ($_GET['dari'] ?? '') === 'admin' ? 'admin/pesanan_detail.php?id=' . (int) $transaksi['id'] : 'index.php';
$labelKembali = ($_GET['dari'] ?? '') === 'admin' ? '&larr; Kembali ke detail pesanan' : '&larr; Kembali ke beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= h($transaksi['kode']) ?> — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
  body{background:#EFE9DA;}
  .invoice-wrap{max-width:720px;margin:2rem auto;padding:0 1rem 3rem;}
  .invoice-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}
  .invoice-toolbar a{font-size:0.88rem;color:var(--clay-dark);font-weight:700;}
  .invoice-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:2rem;}
  .invoice-brand{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid var(--forest);padding-bottom:1.2rem;margin-bottom:1.2rem;}
  .invoice-brand-name{font-family:var(--font-display);font-size:1.5rem;font-weight:600;color:var(--forest);}
  .invoice-brand-tag{font-size:0.78rem;color:var(--muted);}
  .invoice-meta{text-align:right;font-size:0.85rem;color:var(--muted);}
  .invoice-meta strong{display:block;color:var(--ink);font-family:var(--font-display);font-size:1.05rem;}
  .invoice-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.4rem;margin-bottom:1.4rem;}
  .invoice-block-label{font-size:0.72rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--muted);margin-bottom:0.3rem;}
  .invoice-block-value{font-size:0.92rem;line-height:1.5;}
  table.invoice-table{width:100%;border-collapse:collapse;margin-bottom:1.2rem;}
  table.invoice-table th{text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.03em;color:var(--muted);border-bottom:1px solid var(--line);padding:0.5rem 0.4rem;}
  table.invoice-table td{padding:0.6rem 0.4rem;border-bottom:1px solid #F0EAD9;font-size:0.9rem;}
  table.invoice-table td.num,table.invoice-table th.num{text-align:right;white-space:nowrap;}
  .invoice-total-row td{border-bottom:none;padding-top:0.8rem;}
  .invoice-total-label{text-align:right;font-weight:700;font-size:1rem;}
  .invoice-total-value{text-align:right;font-weight:700;font-size:1.15rem;color:var(--forest);}
  .invoice-footnote{margin-top:1.6rem;font-size:0.78rem;color:var(--muted);text-align:center;}
  .btn-print{background:var(--forest);color:#fff;border:none;padding:0.7rem 1.4rem;border-radius:6px;font-weight:700;font-size:0.9rem;cursor:pointer;}
  .btn-print:hover{background:var(--forest-2);}
  @media print{
    body{background:#fff;}
    .invoice-toolbar{display:none;}
    .invoice-wrap{margin:0;padding:0;max-width:none;}
    .invoice-card{border:none;border-radius:0;padding:0;}
  }
</style>
</head>
<body>
<div class="invoice-wrap">
  <div class="invoice-toolbar">
    <a href="<?= h($kembaliKe) ?>"><?= $labelKembali ?></a>
    <button type="button" class="btn-print" onclick="window.print()">🖨 Cetak Invoice</button>
  </div>

  <div class="invoice-card">
    <div class="invoice-brand">
      <div>
        <p class="invoice-brand-name">Laluna<span style="color:var(--clay);">co.</span></p>
        <p class="invoice-brand-tag">Produk rajutan tangan — Madiun, Jawa Timur</p>
      </div>
      <div class="invoice-meta">
        <strong>INVOICE</strong>
        <?= h($transaksi['kode']) ?><br>
        <?= h(date('d M Y, H:i', strtotime($transaksi['dibuat_pada']))) ?>
      </div>
    </div>

    <div class="invoice-grid">
      <div>
        <p class="invoice-block-label">Ditagihkan Kepada</p>
        <p class="invoice-block-value">
          <?= h($transaksi['nama']) ?><br>
          <?= h($transaksi['telepon']) ?><br>
          <?php if (!empty($transaksi['email'])): ?><?= h($transaksi['email']) ?><br><?php endif; ?>
        </p>
      </div>
      <div>
        <p class="invoice-block-label">Alamat Pengiriman</p>
        <p class="invoice-block-value"><?= nl2br(h($transaksi['alamat'])) ?></p>
      </div>
    </div>

    <table class="invoice-table">
      <thead>
        <tr><th>Produk</th><th class="num">Harga</th><th class="num">Jumlah</th><th class="num">Subtotal</th></tr>
      </thead>
      <tbody>
        <?php foreach ($itemList as $it): ?>
        <tr>
          <td><?= h($it['nama_produk']) ?><?= !empty($it['warna']) ? ' — ' . h($it['warna']) : '' ?></td>
          <td class="num"><?= format_rupiah($it['harga']) ?></td>
          <td class="num"><?= (int) $it['jumlah'] ?></td>
          <td class="num"><?= format_rupiah($it['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="invoice-total-row">
          <td colspan="3" class="invoice-total-label">Total</td>
          <td class="invoice-total-value"><?= format_rupiah($transaksi['total']) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="invoice-grid" style="margin-bottom:0;">
      <div>
        <p class="invoice-block-label">Metode Pembayaran</p>
        <p class="invoice-block-value"><?= h($transaksi['metode_pembayaran'] ?: '-') ?></p>
      </div>
      <div>
        <p class="invoice-block-label">Status Pesanan</p>
        <p class="invoice-block-value"><?= h(ucwords($transaksi['status'] ?: 'menunggu konfirmasi')) ?></p>
      </div>
    </div>

    <p class="invoice-footnote">Terima kasih sudah berbelanja di Lalunaco. Simpan invoice ini sebagai bukti transaksi.</p>
  </div>
</div>
</body>
</html>