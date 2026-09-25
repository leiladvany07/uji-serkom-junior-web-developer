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

// Tahapan pesanan untuk timeline visual. Kembalikan -1 kalau pesanan dibatalkan/ditolak,
// supaya timeline diganti dengan banner khusus, bukan menampilkan tahapan yang salah.
function tahap_pesanan_saya($status) {
    $s = mb_strtolower((string) $status);
    if ($s === '' || str_contains($s, 'menunggu') || str_contains($s, 'konfirmasi')) return 0;
    if (str_contains($s, 'batal') || str_contains($s, 'tolak')) return -1;
    if (str_contains($s, 'selesai') || str_contains($s, 'sukses')) return 3;
    if (str_contains($s, 'kirim')) return 2;
    if (str_contains($s, 'proses') || str_contains($s, 'bayar')) return 1;
    return 0;
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

$stmtItem = $pdo->prepare('SELECT ti.*, p.gambar, p.slug FROM transaksi_item ti LEFT JOIN produk p ON p.id = ti.produk_id WHERE ti.transaksi_id = ? ORDER BY ti.id');
$stmtItem->execute([$transaksi['id']]);
$itemList = $stmtItem->fetchAll();

$st = $transaksi['status'] ?: 'menunggu konfirmasi';
$tahap = tahap_pesanan_saya($st);
$langkahPesanan = ['Menunggu Konfirmasi', 'Diproses', 'Dikirim', 'Selesai'];

$page_title = 'Pesanan ' . $transaksi['kode'];
require __DIR__ . '/includes/header.php';
?>

<section class="section pesanan-detail-page">
  <a href="pesanan_saya.php" class="pesanan-back">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    Kembali ke Pesanan Saya
  </a>

  <div class="pesanan-detail-hero">
    <div>
      <p class="pesanan-detail-eyebrow">Kode pesanan</p>
      <h1 class="pesanan-detail-kode"><?= h($transaksi['kode']) ?></h1>
      <p class="pesanan-detail-tanggal"><?= h(date('d M Y, H:i', strtotime($transaksi['dibuat_pada']))) ?> WIB</p>
    </div>
    <div class="pesanan-detail-hero-aksi">
      <span class="status-badge status-badge-lg <?= warna_status_pesanan_saya($st) ?>">
        <span class="status-dot"></span><?= h(ucwords($st)) ?>
      </span>
      <a href="invoice.php?kode=<?= urlencode($transaksi['kode']) ?>" target="_blank" class="icon-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak Invoice</span>
      </a>
    </div>
  </div>

  <?php if ($tahap === -1): ?>
    <div class="pesanan-status-banner is-danger">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
      Pesanan ini <?= str_contains(mb_strtolower($st), 'tolak') ? 'ditolak' : 'dibatalkan' ?>. Ada pertanyaan? Hubungi kami lewat <a href="kontak.php">halaman Kontak</a>.
    </div>
  <?php else: ?>
    <ol class="pesanan-progress">
      <?php foreach ($langkahPesanan as $i => $label): ?>
        <li class="<?= $i < $tahap ? 'is-done' : ($i === $tahap ? 'is-active' : '') ?>">
          <span class="pesanan-progress-dot"><?= $i < $tahap ? '&check;' : $i + 1 ?></span>
          <span class="pesanan-progress-label"><?= h($label) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>

  <div class="pesanan-main-card">
    <div class="pesanan-main-grid">
      <div class="pesanan-detail-items">
        <h2 class="pesanan-detail-subheading">Item Pesanan</h2>
        <?php foreach ($itemList as $it): ?>
          <div class="pesanan-item-row">
            <a class="pesanan-item-thumb" href="<?= !empty($it['slug']) ? 'produk_detail.php?slug=' . urlencode($it['slug']) : '#' ?>">
              <?php if (!empty($it['gambar'])): ?>
                <img src="assets/<?= h(first_image($it['gambar'])) ?>" alt="<?= h($it['nama_produk']) ?>">
              <?php else: ?>
                <span class="pesanan-item-thumb-fallback">?</span>
              <?php endif; ?>
            </a>
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
        <ul class="pesanan-info-list">
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <div><span class="contact-label">Nama Penerima</span><span class="contact-value"><?= h($transaksi['nama']) ?></span></div>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            <div><span class="contact-label">Telepon / WhatsApp</span><span class="contact-value"><?= h($transaksi['telepon']) ?></span></div>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <div><span class="contact-label">Alamat Pengiriman</span><span class="contact-value"><?= nl2br(h($transaksi['alamat'])) ?></span></div>
          </li>
          <?php if (!empty($transaksi['metode_pembayaran'])): ?>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            <div><span class="contact-label">Metode Pembayaran</span><span class="contact-value"><?= h($transaksi['metode_pembayaran']) ?></span></div>
          </li>
          <?php endif; ?>
        </ul>

        <?php if (!empty($transaksi['ekspedisi']) || !empty($transaksi['no_resi'])): ?>
        <div class="pesanan-resi-box">
          <p class="pesanan-resi-judul">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="7" width="15" height="13" rx="2"></rect><path d="M16 10h3l4 4v3a2 2 0 0 1-2 2h-1"></path><circle cx="5.5" cy="20" r="1.5"></circle><circle cx="17.5" cy="20" r="1.5"></circle></svg>
            Info Pengiriman
          </p>
          <div class="pesanan-resi-row"><span>Ekspedisi</span><strong><?= h($transaksi['ekspedisi'] ?: '-') ?></strong></div>
          <div class="pesanan-resi-row"><span>Nomor Resi</span><strong><?= h($transaksi['no_resi'] ?: '-') ?></strong></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>