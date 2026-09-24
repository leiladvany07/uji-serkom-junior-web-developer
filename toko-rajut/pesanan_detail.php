<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/pelanggan_auth.php';

wajib_login_pelanggan();

function warna_status_pesanan_saya($status) {
    $s = mb_strtolower((string) $status);
    if ($s === '') return 'status-new';
    if (str_contains($s, 'batal') || str_contains($s, 'tolak')) return 'status-danger';
    if (str_contains($s, 'selesai') || str_contains($s, 'kirim') || str_contains($s, 'sukses')) return 'status-done';
    if (str_contains($s, 'proses') || str_contains($s, 'konfirmasi') || str_contains($s, 'bayar')) return 'status-progress';
    return 'status-new';
}

$id = (int) ($_GET['id'] ?? 0);

// PENTING: pelanggan_id harus cocok dengan sesi yang sedang login, supaya
// tidak bisa lihat pesanan orang lain hanya dengan mengganti angka di URL.
$stmt = $pdo->prepare('SELECT * FROM transaksi WHERE id = ? AND pelanggan_id = ?');
$stmt->execute([$id, $_SESSION['pelanggan_id']]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    header('Location: pesanan_saya.php');
    exit;
}

$stmtItem = $pdo->prepare('SELECT ti.*, p.gambar FROM transaksi_item ti LEFT JOIN produk p ON p.id = ti.produk_id WHERE ti.transaksi_id = ? ORDER BY ti.id');
$stmtItem->execute([$transaksi['id']]);
$itemList = $stmtItem->fetchAll();

$st = $transaksi['status'] ?: 'menunggu konfirmasi';

$page_title = 'Pesanan ' . $transaksi['kode'];
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <a href="pesanan_saya.php" class="admin-back">&larr; Kembali ke Pesanan Saya</a>

  <div class="pesanan-detail-hero">
    <div>
      <p class="pesanan-detail-eyebrow">Kode pesanan</p>
      <h1 class="pesanan-detail-kode"><?= h($transaksi['kode']) ?></h1>
      <p class="pesanan-detail-tanggal"><?= h(date('d M Y, H:i', strtotime($transaksi['dibuat_pada']))) ?></p>
    </div>
    <span class="status-badge status-badge-lg <?= warna_status_pesanan_saya($st) ?>">
      <span class="status-dot"></span><?= h(ucwords($st)) ?>
    </span>
  </div>

  <p style="margin-bottom:1rem;"><a href="invoice.php?kode=<?= urlencode($transaksi['kode']) ?>" target="_blank" class="btn btn-outline">🖨 Cetak Invoice</a></p>

  <div class="pesanan-main-card">
    <div class="pesanan-main-grid">
      <div class="pesanan-detail-items">
        <h2 class="pesanan-detail-subheading">Item Pesanan</h2>
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
              <?php if (!empty($it['warna'])): ?><p class="pesanan-item-warna">Warna: <?= h($it['warna']) ?></p><?php endif; ?>
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

      <div class="pesanan-detail-side">
        <h2 class="pesanan-detail-subheading">Pengiriman</h2>
        <div class="contact-info">
          <div class="contact-item">
            <span class="contact-label">Nama Penerima</span>
            <span class="contact-value"><?= h($transaksi['nama']) ?></span>
          </div>
          <div class="contact-item">
            <span class="contact-label">Telepon / WhatsApp</span>
            <span class="contact-value"><?= h($transaksi['telepon']) ?></span>
          </div>
          <div class="contact-item">
            <span class="contact-label">Alamat Pengiriman</span>
            <span class="contact-value"><?= nl2br(h($transaksi['alamat'])) ?></span>
          </div>
          <?php if (!empty($transaksi['metode_pembayaran'])): ?>
          <div class="contact-item">
            <span class="contact-label">Metode Pembayaran</span>
            <span class="contact-value"><?= h($transaksi['metode_pembayaran']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($transaksi['ekspedisi']) || !empty($transaksi['no_resi'])): ?>
          <div class="contact-item">
            <span class="contact-label">Ekspedisi</span>
            <span class="contact-value"><?= h($transaksi['ekspedisi'] ?: '-') ?></span>
          </div>
          <div class="contact-item">
            <span class="contact-label">Nomor Resi</span>
            <span class="contact-value"><?= h($transaksi['no_resi'] ?: '-') ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>