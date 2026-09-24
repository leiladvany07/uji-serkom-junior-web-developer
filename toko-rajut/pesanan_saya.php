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

// Hanya pesanan milik pelanggan yang sedang login (pelanggan_id sesuai sesi).
$stmt = $pdo->prepare("SELECT t.*, (SELECT COALESCE(SUM(jumlah),0) FROM transaksi_item WHERE transaksi_id = t.id) AS total_item
    FROM transaksi t WHERE t.pelanggan_id = ? ORDER BY t.dibuat_pada DESC");
$stmt->execute([$_SESSION['pelanggan_id']]);
$pesananList = $stmt->fetchAll();

$page_title = 'Pesanan Saya';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <h1 class="section-title">Pesanan Saya</h1>
  <p class="checkout-note" style="margin-top:-0.8rem;margin-bottom:1.4rem;">Halo, <?= h($_SESSION['pelanggan_nama']) ?>. Berikut riwayat pesananmu.</p>

  <div class="pesanan-list">
    <?php foreach ($pesananList as $t): ?>
      <?php $st = $t['status'] ?: 'menunggu konfirmasi'; ?>
      <article class="pesanan-card <?= warna_status_pesanan_saya($st) ?>-border">
        <div class="pesanan-card-left">
          <div class="pesanan-card-main">
            <div class="pesanan-card-top">
              <strong class="pesanan-kode"><?= h($t['kode']) ?></strong>
              <span class="pesanan-tanggal"><?= h(date('d M Y, H:i', strtotime($t['dibuat_pada']))) ?></span>
            </div>
            <p class="pesanan-ringkas"><?= (int) $t['total_item'] ?> pcs &middot; <span class="pesanan-total"><?= format_rupiah($t['total']) ?></span></p>
          </div>
        </div>
        <div class="pesanan-card-right">
          <span class="status-badge <?= warna_status_pesanan_saya($st) ?>"><span class="status-dot"></span><?= h(ucwords($st)) ?></span>
          <div class="pesanan-card-actions">
            <a class="icon-btn" href="pesanan_detail.php?id=<?= $t['id'] ?>" title="Lihat detail">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              <span>Detail</span>
            </a>
            <a class="icon-btn" href="invoice.php?kode=<?= urlencode($t['kode']) ?>" target="_blank" title="Cetak invoice">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
              <span>Invoice</span>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (empty($pesananList)): ?>
      <p class="empty-state">Kamu belum punya pesanan. <a href="produk.php" style="color:var(--clay);font-weight:700;">Mulai belanja</a>.</p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>