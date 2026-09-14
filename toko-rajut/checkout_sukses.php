<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

$keranjang = $_SESSION['keranjang'] ?? [];
if (empty($keranjang)) {
    header('Location: keranjang.php');
    exit;
}

$ids = array_keys($keranjang);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
$stmt->execute($ids);
$produkList = $stmt->fetchAll();

$items = [];
$total = 0;
foreach ($produkList as $p) {
    $jumlah = $keranjang[$p['id']];
    $subtotal = $p['harga'] * $jumlah;
    $total += $subtotal;
    $items[] = ['produk' => $p, 'jumlah' => $jumlah, 'subtotal' => $subtotal];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if ($telepon === '') $errors[] = 'Nomor telepon wajib diisi.';
    if ($alamat === '') $errors[] = 'Alamat pengiriman wajib diisi.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    if (empty($errors)) {
        $kode = 'LLC-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO transaksi (kode, nama, telepon, email, alamat, catatan, total) VALUES (?,?,?,?,?,?,?) RETURNING id');
            $stmt->execute([$kode, $nama, $telepon, $email ?: null, $alamat, $catatan ?: null, $total]);
            $transaksiId = $stmt->fetchColumn();

            $stmtItem = $pdo->prepare('INSERT INTO transaksi_item (transaksi_id, produk_id, nama_produk, harga, jumlah, subtotal) VALUES (?,?,?,?,?,?)');
            $stmtStok = $pdo->prepare('UPDATE produk SET stok = GREATEST(stok - ?, 0) WHERE id = ?');

            foreach ($items as $item) {
                $p = $item['produk'];
                $stmtItem->execute([$transaksiId, $p['id'], $p['nama'], $p['harga'], $item['jumlah'], $item['subtotal']]);
                $stmtStok->execute([$item['jumlah'], $p['id']]);
            }

            $pdo->commit();
            unset($_SESSION['keranjang']);
            header('Location: checkout_sukses.php?kode=' . urlencode($kode));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.';
            error_log('Checkout gagal: ' . $e->getMessage());
        }
    }
}

$page_title = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <h1 class="section-title">Checkout</h1>

  <?php if (!empty($errors)): ?>
    <ul class="form-errors">
      <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="contact-layout">
    <form class="contact-form" method="post" action="checkout.php" novalidate>
      <label>Nama Penerima
        <input type="text" name="nama" value="<?= h($_POST['nama'] ?? '') ?>" required>
      </label>
      <label>Nomor Telepon / WhatsApp
        <input type="text" name="telepon" value="<?= h($_POST['telepon'] ?? '') ?>" required>
      </label>
      <label>Email (opsional)
        <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>">
      </label>
      <label>Alamat Pengiriman
        <textarea name="alamat" rows="3" required><?= h($_POST['alamat'] ?? '') ?></textarea>
      </label>
      <label>Catatan (opsional)
        <textarea name="catatan" rows="2"><?= h($_POST['catatan'] ?? '') ?></textarea>
      </label>
      <button type="submit" class="btn btn-primary">Buat Pesanan</button>
    </form>

    <div class="cart-summary">
      <h2 class="cart-summary-title">Pesanan Anda</h2>
      <?php foreach ($items as $item): $p = $item['produk']; ?>
        <div class="checkout-summary-row">
          <span><?= h($p['nama']) ?> &times; <?= $item['jumlah'] ?></span>
          <span><?= format_rupiah($item['subtotal']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="cart-summary-row">
        <span>Total</span>
        <span class="cart-summary-total"><?= format_rupiah($total) ?></span>
      </div>
      <p class="checkout-note">Pembayaran dan ongkos kirim akan dikonfirmasi lewat WhatsApp setelah pesanan dibuat.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>