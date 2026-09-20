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

$stmtItem = $pdo->prepare('SELECT ti.*, p.gambar FROM transaksi_item ti LEFT JOIN produk p ON p.id = ti.produk_id WHERE ti.transaksi_id = ? ORDER BY ti.id');
$stmtItem->execute([$transaksi['id']]);
$itemList = $stmtItem->fetchAll();

// Pesan yang dikirim CUSTOMER ke admin lewat WhatsApp (bukan sebaliknya).
// Customer tap tombol & kirim -> otomatis kamu (admin) yang menerima WA-nya.
$pesanWaKonfirmasi = "Halo, saya baru saja membuat pesanan dengan kode {$transaksi['kode']} atas nama {$transaksi['nama']}. Mohon dikonfirmasi ya, terima kasih.";
$linkWaKonfirmasi = 'https://wa.me/6288989505932?text=' . rawurlencode($pesanWaKonfirmasi);

$page_title = 'Pesanan Berhasil Dibuat';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="checkout-sukses-wrap">
    <div class="checkout-sukses-icon">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    </div>
    <h1 class="section-title">Pesanan Berhasil Dibuat!</h1>
    <p class="checkout-sukses-lead">Terima kasih, <?= h($transaksi['nama']) ?>. Pesanan kamu sudah kami terima dan akan segera diproses.</p>

    <div class="pesanan-main-card">
      <div class="riwayat-head">
        <div>
          <p class="pesanan-detail-eyebrow">Kode pesanan</p>
          <strong class="pesanan-kode"><?= h($transaksi['kode']) ?></strong>
        </div>
        <span class="status-badge status-new"><span class="status-dot"></span>Menunggu Konfirmasi</span>
      </div>

      <?php foreach ($itemList as $it): ?>
        <div class="pesanan-item-row">
          <div class="pesanan-item-thumb">
            <?php if (!empty($it['gambar'])): ?>
              <img src="assets/<?= h(first_image($it['gambar'])) ?>" alt="<?= h($it['nama_produk']) ?>">
            <?php else: ?>
              <span class="pesanan-item-thumb-fallback">?</span>
            <?php endif; ?>
          </div>
          <div class="pesanan-item-info">
            <p class="pesanan-item-nama"><?= h($it['nama_produk']) ?></p>
            <p class="pesanan-item-harga"><?= format_rupiah($it['harga']) ?> &times; <?= (int) $it['jumlah'] ?></p>
          </div>
          <div class="pesanan-item-subtotal"><?= format_rupiah($it['subtotal']) ?></div>
        </div>
      <?php endforeach; ?>

      <div class="pesanan-total-row pesanan-total-row-flat">
        <span>Total Pesanan</span>
        <span class="cart-summary-total"><?= format_rupiah($transaksi['total']) ?></span>
      </div>
    </div>

    <p class="checkout-note checkout-sukses-note">Simpan kode pesanan di atas ya. Admin Lalunaco akan menghubungi kamu lewat WhatsApp di nomor <strong><?= h($transaksi['telepon']) ?></strong> untuk konfirmasi ongkos kirim dan pembayaran.</p>

    <div class="checkout-sukses-actions">
      <a class="icon-btn icon-btn-wa" href="<?= h($linkWaKonfirmasi) ?>" target="_blank" rel="noopener">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.3A8.9 8.9 0 0 0 12 4a8.9 8.9 0 0 0-7.8 13.4L3 21l3.7-1.2A8.9 8.9 0 0 0 12 21a8.9 8.9 0 0 0 5.6-15.7zM12 19.3a7.3 7.3 0 0 1-3.9-1.1l-.3-.2-2.6.9.8-2.5-.2-.3A7.3 7.3 0 1 1 19.3 12 7.3 7.3 0 0 1 12 19.3z"/></svg>
        <span>Konfirmasi via WhatsApp</span>
      </a>
      <a href="produk.php" class="btn btn-primary">Lanjut Belanja</a>
      <a href="index.php" class="btn btn-outline">Kembali ke Beranda</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>