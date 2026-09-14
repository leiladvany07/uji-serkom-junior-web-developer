<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

// Tambah produk ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_id'])) {
    $id = (int) $_POST['tambah_id'];
    $jumlah = max(1, (int) ($_POST['jumlah'] ?? 1));
    if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];
    $_SESSION['keranjang'][$id] = ($_SESSION['keranjang'][$id] ?? 0) + $jumlah;
    header('Location: keranjang.php');
    exit;
}

// Update jumlah item di keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = (int) $_POST['update_id'];
    $jumlah = (int) ($_POST['jumlah'] ?? 1);
    if ($jumlah <= 0) {
        unset($_SESSION['keranjang'][$id]);
    } else {
        $_SESSION['keranjang'][$id] = $jumlah;
    }
    header('Location: keranjang.php');
    exit;
}

// Hapus item dari keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    unset($_SESSION['keranjang'][(int) $_POST['hapus_id']]);
    header('Location: keranjang.php');
    exit;
}

$keranjang = $_SESSION['keranjang'] ?? [];
$items = [];
$total = 0;

if (!empty($keranjang)) {
    $ids = array_keys($keranjang);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $produkList = $stmt->fetchAll();

    foreach ($produkList as $p) {
        $jumlah = $keranjang[$p['id']];
        $subtotal = $p['harga'] * $jumlah;
        $total += $subtotal;
        $items[] = [
            'produk' => $p,
            'jumlah' => $jumlah,
            'subtotal' => $subtotal,
        ];
    }
}

$page_title = 'Keranjang Belanja';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <h1 class="section-title">Keranjang Belanja</h1>

  <?php if (empty($items)): ?>
    <p class="empty-state">Keranjang Anda masih kosong. <a href="produk.php">Lihat produk →</a></p>
  <?php else: ?>
    <div class="cart-layout">
      <div class="cart-items">
        <?php foreach ($items as $item): $p = $item['produk']; ?>
          <div class="cart-item">
            <a href="produk_detail.php?slug=<?= h($p['slug']) ?>" class="cart-item-thumb">
              <img src="assets/<?= h(first_image($p['gambar'])) ?>" alt="<?= h($p['nama']) ?>">
            </a>
            <div class="cart-item-info">
              <h3 class="cart-item-nama"><?= h($p['nama']) ?></h3>
              <p class="cart-item-harga"><?= format_rupiah($p['harga']) ?></p>
              <form method="post" action="keranjang.php" class="cart-item-qty-form">
                <input type="hidden" name="update_id" value="<?= $p['id'] ?>">
                <button type="submit" name="jumlah" value="<?= $item['jumlah'] - 1 ?>" class="qty-btn">&minus;</button>
                <span class="qty-value"><?= $item['jumlah'] ?></span>
                <button type="submit" name="jumlah" value="<?= $item['jumlah'] + 1 ?>" class="qty-btn">&plus;</button>
              </form>
            </div>
            <div class="cart-item-side">
              <p class="cart-item-subtotal"><?= format_rupiah($item['subtotal']) ?></p>
              <form method="post" action="keranjang.php">
                <input type="hidden" name="hapus_id" value="<?= $p['id'] ?>">
                <button type="submit" class="cart-item-hapus">Hapus</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="cart-summary">
        <h2 class="cart-summary-title">Ringkasan</h2>
        <div class="cart-summary-row">
          <span>Total</span>
          <span class="cart-summary-total"><?= format_rupiah($total) ?></span>
        </div>
        <a href="checkout.php" class="btn btn-primary cart-checkout-btn">Lanjut ke Checkout</a>
        <a href="produk.php" class="cart-continue-link">&larr; Lanjut belanja</a>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>