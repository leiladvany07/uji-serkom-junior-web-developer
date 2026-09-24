<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

// Tambah produk ke keranjang (dengan validasi status & stok)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_id'])) {
    $id = (int) $_POST['tambah_id'];
    $warna = trim($_POST['warna'] ?? '');
    $jumlah = max(1, (int) ($_POST['jumlah'] ?? 1));
    $key = $id . '::' . $warna;
    if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];

    $stmtP = $pdo->prepare('SELECT id, nama, stok, aktif FROM produk WHERE id = ?');
    $stmtP->execute([$id]);
    $p = $stmtP->fetch();
    $flash = [];

    if (!$p || !produk_aktif($p)) {
        $flash[] = 'Produk ini sudah tidak tersedia.';
    } elseif ((int) $p['stok'] <= 0) {
        $flash[] = 'Stok "' . $p['nama'] . '" sedang habis.';
    } else {
        // Stok dipakai bersama semua pilihan warna produk yang sama.
        $sudah = 0;
        foreach ($_SESSION['keranjang'] as $k => $j) {
            if (id_dari_key_keranjang($k) === $id) $sudah += $j;
        }
        $sisa = (int) $p['stok'] - $sudah;
        if ($sisa <= 0) {
            $flash[] = 'Keranjangmu sudah berisi seluruh stok "' . $p['nama'] . '" (' . (int) $p['stok'] . ' pcs).';
        } else {
            $tambah = min($jumlah, $sisa);
            $_SESSION['keranjang'][$key] = ($_SESSION['keranjang'][$key] ?? 0) + $tambah;
            if ($tambah < $jumlah) {
                $flash[] = 'Hanya ' . $tambah . ' pcs "' . $p['nama'] . '" yang ditambahkan karena stok tersisa terbatas.';
            }
        }
    }

    if ($flash) {
        // Ada penyesuaian / penolakan: tampilkan pesannya di halaman keranjang.
        $_SESSION['keranjang_pesan'] = $flash;
        header('Location: keranjang.php');
        exit;
    }

    $kembali = $_POST['kembali'] ?? '';
    // Hanya izinkan redirect ke halaman lokal produk_detail.php demi keamanan.
    if (is_string($kembali) && preg_match('/^produk_detail\.php\?slug=[a-z0-9\-]+(&ditambahkan=1)?$/', $kembali)) {
        header('Location: ' . $kembali);
    } else {
        header('Location: keranjang.php');
    }
    exit;
}

// Update jumlah item di keranjang (tidak boleh melebihi stok)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $key = (string) $_POST['update_id'];
    $jumlah = (int) ($_POST['jumlah'] ?? 1);
    if ($jumlah <= 0 || !isset($_SESSION['keranjang'][$key])) {
        unset($_SESSION['keranjang'][$key]);
    } else {
        $id = id_dari_key_keranjang($key);
        $stmtP = $pdo->prepare('SELECT id, nama, stok, aktif FROM produk WHERE id = ?');
        $stmtP->execute([$id]);
        $p = $stmtP->fetch();
        if (!$p || !produk_aktif($p)) {
            unset($_SESSION['keranjang'][$key]);
            $_SESSION['keranjang_pesan'] = ['Produk ini sudah tidak tersedia dan dihapus dari keranjang.'];
        } else {
            $lain = 0;
            foreach ($_SESSION['keranjang'] as $k => $j) {
                if ($k !== $key && id_dari_key_keranjang($k) === $id) $lain += $j;
            }
            $maks = (int) $p['stok'] - $lain;
            if ($maks <= 0) {
                unset($_SESSION['keranjang'][$key]);
                $_SESSION['keranjang_pesan'] = ['Stok "' . $p['nama'] . '" habis.'];
            } elseif ($jumlah > $maks) {
                $_SESSION['keranjang'][$key] = $maks;
                $_SESSION['keranjang_pesan'] = ['Stok "' . $p['nama'] . '" hanya tersisa ' . (int) $p['stok'] . ' pcs, jumlah disesuaikan.'];
            } else {
                $_SESSION['keranjang'][$key] = $jumlah;
            }
        }
    }
    header('Location: keranjang.php');
    exit;
}

// Hapus item dari keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    unset($_SESSION['keranjang'][(string) $_POST['hapus_id']]);
    header('Location: keranjang.php');
    exit;
}

// Samakan keranjang dengan stok & status produk terbaru sebelum ditampilkan.
$pesanKeranjang = $_SESSION['keranjang_pesan'] ?? [];
unset($_SESSION['keranjang_pesan']);
$pesanKeranjang = array_merge($pesanKeranjang, sinkron_keranjang($pdo));

$keranjang = $_SESSION['keranjang'] ?? [];
$items = [];
$total = 0;

if (!empty($keranjang)) {
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
        $items[] = [
            'key' => $key,
            'produk' => $p,
            'warna' => $keyInfo[$key]['warna'],
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

  <?php if (!empty($pesanKeranjang)): ?>
    <ul class="form-errors" style="margin-bottom:1.2rem;">
      <?php foreach ($pesanKeranjang as $m): ?><li><?= h($m) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

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
              <h3 class="cart-item-nama">
                <?= h($p['nama']) ?>
                <?php if ($item['warna'] !== ''): ?><span class="cart-item-warna">(<?= h($item['warna']) ?>)</span><?php endif; ?>
              </h3>
              <p class="cart-item-harga"><?= format_rupiah($p['harga']) ?></p>
              <form method="post" action="keranjang.php" class="cart-item-qty-form">
                <input type="hidden" name="update_id" value="<?= h($item['key']) ?>">
                <button type="submit" name="jumlah" value="<?= $item['jumlah'] - 1 ?>" class="qty-btn">&minus;</button>
                <span class="qty-value"><?= $item['jumlah'] ?></span>
                <button type="submit" name="jumlah" value="<?= $item['jumlah'] + 1 ?>" class="qty-btn">&plus;</button>
              </form>
            </div>
            <div class="cart-item-side">
              <p class="cart-item-subtotal"><?= format_rupiah($item['subtotal']) ?></p>
              <form method="post" action="keranjang.php">
                <input type="hidden" name="hapus_id" value="<?= h($item['key']) ?>">
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