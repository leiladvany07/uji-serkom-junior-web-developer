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
  /* ============ Halaman invoice — presentasi saja, tidak menyentuh logika PHP ============ */
  body{background:var(--bg);}

  .invoice-wrap{max-width:760px;margin:2.4rem auto;padding:0 1.25rem 3.5rem;}

  .invoice-toolbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.4rem;flex-wrap:wrap;}
  .invoice-back{display:inline-flex;align-items:center;gap:0.4rem;font-size:0.88rem;font-weight:700;color:var(--clay-dark);transition:transform .15s ease;}
  .invoice-back:hover{transform:translateX(-3px);}
  .invoice-back svg{flex-shrink:0;}
  .btn-print{
    display:inline-flex;align-items:center;gap:0.5rem;
    background:var(--forest);color:#fff;border:none;
    padding:0.7rem 1.35rem;border-radius:8px;
    font-weight:700;font-size:0.88rem;cursor:pointer;
    box-shadow:var(--shadow-sm,0 1px 2px rgba(43,38,32,0.05));
    transition:background .15s ease, box-shadow .15s ease, transform .15s ease;
  }
  .btn-print:hover{background:var(--forest-2);box-shadow:var(--shadow-md,0 6px 18px rgba(43,38,32,0.08));transform:translateY(-1px);}
  .btn-print svg{flex-shrink:0;}

  .invoice-card{
    background:var(--card,#fff);
    border:1px solid var(--line);
    border-radius:16px;
    overflow:hidden;
    box-shadow:var(--shadow-md,0 6px 18px rgba(43,38,32,0.08));
  }

  /* ---- Header: logo kiri, meta kanan, di atas dasar cream lembut ---- */
  .invoice-header{
    display:flex;justify-content:space-between;align-items:flex-start;gap:1.2rem;flex-wrap:wrap;
    padding:2rem 2.2rem 1.6rem;
    background:linear-gradient(180deg,#FBF6EA 0%, var(--card,#fff) 100%);
  }
  .invoice-brand-mark{display:flex;align-items:center;gap:0.8rem;}
  .invoice-brand-icon{
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
    width:42px;height:42px;border-radius:11px;
    background:var(--forest);color:#F3ECDD;
    font-family:var(--font-display);font-weight:600;font-size:1.15rem;
  }
  .invoice-brand-name{font-family:var(--font-display);font-size:1.55rem;font-weight:600;color:var(--forest);line-height:1.1;}
  .invoice-brand-tag{font-size:0.78rem;color:var(--muted);margin-top:0.2rem;}
  .invoice-meta{text-align:right;}
  .invoice-meta-label{font-size:0.7rem;font-weight:700;letter-spacing:0.12em;color:var(--clay-dark);text-transform:uppercase;}
  .invoice-meta-kode{font-family:var(--font-display);font-size:1.2rem;font-weight:600;color:var(--ink);margin-top:0.3rem;}
  .invoice-meta-tanggal{font-size:0.82rem;color:var(--muted);margin-top:0.2rem;}

  .invoice-divider{height:1px;background:var(--line);margin:0;}

  .invoice-body{padding:1.8rem 2.2rem 2.2rem;}

  /* ---- Info pembeli & alamat: dua kolom ---- */
  .invoice-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.6rem;margin-bottom:1.8rem;}
  .invoice-block-label{font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--clay-dark);margin-bottom:0.5rem;}
  .invoice-block-value{font-size:0.92rem;line-height:1.65;color:var(--ink);}

  /* ---- Tabel produk ---- */
  table.invoice-table{width:100%;border-collapse:collapse;margin-bottom:0;}
  table.invoice-table thead th{
    text-align:left;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;
    color:var(--muted);border-bottom:1px solid var(--line);padding:0 0.4rem 0.6rem;
  }
  table.invoice-table td{padding:0.8rem 0.4rem;border-bottom:1px solid #F0EAD9;font-size:0.9rem;vertical-align:top;}
  table.invoice-table tbody tr:last-child td{border-bottom:none;}
  table.invoice-table td.num,table.invoice-table th.num{text-align:right;white-space:nowrap;}
  .invoice-produk-nama{font-weight:600;color:var(--ink);}
  .invoice-produk-varian{display:block;font-size:0.8rem;color:var(--muted);margin-top:0.15rem;}

  /* ---- Total: menonjol tapi tetap minimalis ---- */
  .invoice-total-box{
    display:flex;justify-content:flex-end;align-items:center;gap:0.9rem;
    background:#F1F5EC;border-radius:10px;
    margin-top:1rem;padding:0.9rem 1.2rem;
  }
  .invoice-total-label{font-size:0.85rem;font-weight:700;color:var(--forest-2);}
  .invoice-total-value{font-family:var(--font-display);font-size:1.35rem;font-weight:600;color:var(--forest);}

  /* ---- Metode pembayaran & status: dua kolom ---- */
  .invoice-foot-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.6rem;align-items:start;margin-top:1.8rem;padding-top:1.6rem;border-top:1px solid var(--line);}

  .invoice-status-badge{
    display:inline-flex;align-items:center;gap:0.4rem;
    padding:0.35rem 0.85rem;border-radius:20px;
    font-size:0.8rem;font-weight:700;
  }
  .invoice-status-badge .status-dot{width:6px;height:6px;border-radius:50%;}
  .invoice-status-new{background:#E3ECD9;color:var(--forest-2);}
  .invoice-status-new .status-dot{background:var(--forest-2);}
  .invoice-status-progress{background:#F6E8D2;color:var(--clay-dark);}
  .invoice-status-progress .status-dot{background:var(--clay-dark);}
  .invoice-status-done{background:#DCEBE1;color:#1f6b38;}
  .invoice-status-done .status-dot{background:#1f6b38;}
  .invoice-status-danger{background:#F6DCDC;color:#A33131;}
  .invoice-status-danger .status-dot{background:#A33131;}

  .invoice-footnote{margin-top:2rem;font-size:0.8rem;color:var(--muted);text-align:center;}

  /* ---- Cetak: proporsional, tanpa elemen navigasi ---- */
  @page{ size:auto; margin:14mm; }
  @media print{
    body{background:#fff;}
    .invoice-toolbar{display:none;}
    .invoice-wrap{margin:0;padding:0;max-width:none;}
    .invoice-card{border:none;border-radius:0;box-shadow:none;}
    .invoice-header{background:none;padding:0 0 1.2rem;}
    .invoice-body{padding:1.4rem 0 0;}
    .invoice-total-box{background:none;border:1px solid var(--line);}
  }

  @media (max-width:640px){
    .invoice-wrap{margin:1.2rem auto;padding:0 0.85rem 2.5rem;}
    .invoice-header{padding:1.5rem 1.3rem 1.2rem;}
    .invoice-body{padding:1.4rem 1.3rem 1.6rem;}
    .invoice-meta{text-align:left;}
    .invoice-grid,.invoice-foot-grid{grid-template-columns:1fr;gap:1.2rem;}
    .invoice-total-box{justify-content:space-between;}
    table.invoice-table{display:block;overflow-x:auto;-webkit-overflow-scrolling:touch;}
  }
</style>
</head>
<body>
<?php
// Pemetaan status -> warna badge. Murni untuk tampilan; nilai status asli
// dari database ($transaksi['status']) tidak diubah sama sekali.
$statusInvoiceLabel = ucwords($transaksi['status'] ?: 'menunggu konfirmasi');
$statusInvoiceLower = mb_strtolower($statusInvoiceLabel);
if (str_contains($statusInvoiceLower, 'batal') || str_contains($statusInvoiceLower, 'tolak')) {
    $statusInvoiceKelas = 'invoice-status-danger';
} elseif (str_contains($statusInvoiceLower, 'selesai') || str_contains($statusInvoiceLower, 'kirim') || str_contains($statusInvoiceLower, 'sukses')) {
    $statusInvoiceKelas = 'invoice-status-done';
} elseif (str_contains($statusInvoiceLower, 'proses') || str_contains($statusInvoiceLower, 'konfirmasi') || str_contains($statusInvoiceLower, 'bayar')) {
    $statusInvoiceKelas = 'invoice-status-progress';
} else {
    $statusInvoiceKelas = 'invoice-status-new';
}
?>
<div class="invoice-wrap">
  <div class="invoice-toolbar">
    <a href="<?= h($kembaliKe) ?>" class="invoice-back">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      <?= $labelKembali ?>
    </a>
    <button type="button" class="btn-print" onclick="window.print()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      Cetak Invoice
    </button>
  </div>

  <div class="invoice-card">
    <div class="invoice-header">
      <div class="invoice-brand-mark">
        <span class="invoice-brand-icon">L</span>
        <div>
          <p class="invoice-brand-name">Laluna<span style="color:var(--clay);">co.</span></p>
          <p class="invoice-brand-tag">Produk rajutan tangan — Madiun, Jawa Timur</p>
        </div>
      </div>
      <div class="invoice-meta">
        <p class="invoice-meta-label">Invoice</p>
        <p class="invoice-meta-kode"><?= h($transaksi['kode']) ?></p>
        <p class="invoice-meta-tanggal"><?= h(date('d M Y, H:i', strtotime($transaksi['dibuat_pada']))) ?></p>
      </div>
    </div>

    <div class="invoice-divider"></div>

    <div class="invoice-body">
      <div class="invoice-grid">
        <div>
          <p class="invoice-block-label">Informasi Pembeli</p>
          <p class="invoice-block-value">
            <?= h($transaksi['nama']) ?><br>
            <?= h($transaksi['telepon']) ?><br>
            <?php if (!empty($transaksi['email'])): ?><?= h($transaksi['email']) ?><?php endif; ?>
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
            <td>
              <span class="invoice-produk-nama"><?= h($it['nama_produk']) ?></span>
              <?php if (!empty($it['warna'])): ?><span class="invoice-produk-varian">Warna: <?= h($it['warna']) ?></span><?php endif; ?>
            </td>
            <td class="num"><?= format_rupiah($it['harga']) ?></td>
            <td class="num"><?= (int) $it['jumlah'] ?></td>
            <td class="num"><?= format_rupiah($it['subtotal']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="invoice-total-box">
        <span class="invoice-total-label">Total</span>
        <span class="invoice-total-value"><?= format_rupiah($transaksi['total']) ?></span>
      </div>

      <div class="invoice-foot-grid">
        <div>
          <p class="invoice-block-label">Metode Pembayaran</p>
          <p class="invoice-block-value"><?= h($transaksi['metode_pembayaran'] ?: '-') ?></p>
        </div>
        <div>
          <p class="invoice-block-label">Status Pesanan</p>
          <span class="invoice-status-badge <?= $statusInvoiceKelas ?>"><span class="status-dot"></span><?= h($statusInvoiceLabel) ?></span>
        </div>
      </div>

      <p class="invoice-footnote">Terima kasih sudah berbelanja di Lalunaco. Simpan invoice ini sebagai bukti transaksi.</p>
    </div>
  </div>
</div>
</body>
</html>