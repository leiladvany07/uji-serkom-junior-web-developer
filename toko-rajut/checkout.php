<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

// Mode "Beli Sekarang": datang dari tombol di halaman detail produk (GET),
// atau tetap dipertahankan lewat session selama proses checkout (POST).
$langsung = null;
if (isset($_GET['beli_id'])) {
    $langsung = [
        'id' => (int) $_GET['beli_id'],
        'warna' => trim($_GET['beli_warna'] ?? ''),
        'jumlah' => max(1, (int) ($_GET['beli_jumlah'] ?? 1)),
    ];
    $_SESSION['checkout_langsung'] = $langsung;
} elseif (isset($_SESSION['checkout_langsung'])) {
    $langsung = $_SESSION['checkout_langsung'];
}

$items = [];
$total = 0;

if ($langsung) {
    $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ?');
    $stmt->execute([$langsung['id']]);
    $p = $stmt->fetch();
    if (!$p) {
        unset($_SESSION['checkout_langsung']);
        header('Location: produk.php');
        exit;
    }
    $stokTersedia = max(1, (int) $p['stok']);
    $jumlah = min($langsung['jumlah'], $stokTersedia);
    $subtotal = $p['harga'] * $jumlah;
    $items[] = ['produk' => $p, 'warna' => $langsung['warna'], 'jumlah' => $jumlah, 'subtotal' => $subtotal];
    $total = $subtotal;
} else {
    $keranjang = $_SESSION['keranjang'] ?? [];
    if (empty($keranjang)) {
        header('Location: keranjang.php');
        exit;
    }

    $keyInfo = [];
    foreach (array_keys($keranjang) as $key) {
        $parts = explode('::', $key, 2);
        $keyInfo[$key] = ['id' => (int) $parts[0], 'warna' => $parts[1] ?? ''];
    }
    $ids = array_values(array_unique(array_column($keyInfo, 'id')));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $produkById = [];
    foreach ($stmt->fetchAll() as $p) { $produkById[$p['id']] = $p; }

    foreach ($keranjang as $key => $jumlah) {
        $id = $keyInfo[$key]['id'];
        if (!isset($produkById[$id])) continue;
        $p = $produkById[$id];
        $subtotal = $p['harga'] * $jumlah;
        $total += $subtotal;
        $items[] = ['produk' => $p, 'warna' => $keyInfo[$key]['warna'], 'jumlah' => $jumlah, 'subtotal' => $subtotal];
    }
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

            $stmtItem = $pdo->prepare('INSERT INTO transaksi_item (transaksi_id, produk_id, nama_produk, warna, harga, jumlah, subtotal) VALUES (?,?,?,?,?,?,?)');
            $stmtStok = $pdo->prepare('UPDATE produk SET stok = GREATEST(stok - ?, 0) WHERE id = ?');

            foreach ($items as $item) {
                $p = $item['produk'];
                $namaProduk = $p['nama'] . ($item['warna'] !== '' ? ' (' . $item['warna'] . ')' : '');
                $stmtItem->execute([$transaksiId, $p['id'], $namaProduk, $item['warna'], $p['harga'], $item['jumlah'], $item['subtotal']]);
                $stmtStok->execute([$item['jumlah'], $p['id']]);
            }

            $pdo->commit();
            if ($langsung) {
                unset($_SESSION['checkout_langsung']);
            } else {
                unset($_SESSION['keranjang']);
            }
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
       <label>Metode Pembayaran
        <textarea name="pembayaran" rows="2"><?= h($_POST['pembayaran'] ?? '') ?></textarea>
      </label>
      <button type="submit" class="btn btn-primary">Buat Pesanan</button>
    </form>

    <div class="cart-summary">
      <h2 class="cart-summary-title">Pesanan Anda</h2>
      <?php foreach ($items as $item): $p = $item['produk']; ?>
        <div class="checkout-summary-row">
          <span><?= h($p['nama']) ?><?= $item['warna'] !== '' ? ' (' . h($item['warna']) . ')' : '' ?> &times; <?= $item['jumlah'] ?></span>
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