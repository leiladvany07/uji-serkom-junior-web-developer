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

$pelangganData = null;
if (!empty($_SESSION['pelanggan_id'])) {
    $stmtP = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ?');
    $stmtP->execute([$_SESSION['pelanggan_id']]);
    $pelangganData = $stmtP->fetch();
}

$alamatTersimpan = trim($pelangganData['alamat'] ?? '');
$punyaAlamatTersimpan = ($pelangganData && $alamatTersimpan !== '');
// Default: pakai alamat tersimpan (kalau ada). Setelah POST, ikuti pilihan terakhir.
$modeAlamat = $punyaAlamatTersimpan ? (($_POST['mode_alamat'] ?? 'tersimpan') === 'lain' ? 'lain' : 'tersimpan') : 'lain';

$statusPembayaranOptions = ['Transfer Bank', 'QRIS', 'E-Wallet (DANA/OVO/GoPay)', 'COD (Bayar di Tempat)'];
$bankOptions = ['BCA', 'BRI', 'BNI', 'Mandiri', 'CIMB Niaga', 'Bank lainnya'];
$ewalletOptions = ['DANA', 'OVO', 'GoPay', 'ShopeePay', 'E-wallet lainnya'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // Kalau pilih "alamat tersimpan", ambil dari database (bukan dari input form).
    $alamat = ($modeAlamat === 'tersimpan') ? $alamatTersimpan : trim($_POST['alamat'] ?? '');
    $simpanAlamat = !empty($_POST['simpan_alamat']);
    $catatan = trim($_POST['catatan'] ?? '');
    $pembayaran = trim($_POST['pembayaran'] ?? '');
    $bankPilihan = trim($_POST['bank_pilihan'] ?? '');
    $ewalletPilihan = trim($_POST['ewallet_pilihan'] ?? '');

    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if ($telepon === '') $errors[] = 'Nomor telepon wajib diisi.';
    if ($alamat === '') $errors[] = 'Alamat pengiriman wajib diisi.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!in_array($pembayaran, $statusPembayaranOptions, true)) $errors[] = 'Pilih metode pembayaran.';
    if ($pembayaran === 'Transfer Bank' && !in_array($bankPilihan, $bankOptions, true)) $errors[] = 'Pilih bank tujuan transfer.';
    if ($pembayaran === 'E-Wallet (DANA/OVO/GoPay)' && !in_array($ewalletPilihan, $ewalletOptions, true)) $errors[] = 'Pilih jenis e-wallet.';

    $pembayaranFinal = $pembayaran;
    if ($pembayaran === 'Transfer Bank' && $bankPilihan !== '') $pembayaranFinal = 'Transfer Bank - ' . $bankPilihan;
    if ($pembayaran === 'E-Wallet (DANA/OVO/GoPay)' && $ewalletPilihan !== '') $pembayaranFinal = 'E-Wallet - ' . $ewalletPilihan;

    if (empty($errors)) {
        $kode = 'LLC-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO transaksi (kode, nama, telepon, email, alamat, catatan, metode_pembayaran, total, pelanggan_id) VALUES (?,?,?,?,?,?,?,?,?) RETURNING id');
            $stmt->execute([$kode, $nama, $telepon, $email ?: null, $alamat, $catatan ?: null, $pembayaranFinal, $total, $_SESSION['pelanggan_id'] ?? null]);
            $transaksiId = $stmt->fetchColumn();

            // Simpan alamat ke akun hanya kalau: belum punya alamat tersimpan,
            // atau pelanggan mencentang "jadikan alamat tersimpan" saat pakai alamat lain.
            // Memakai alamat lain tanpa centang tidak menimpa alamat utama.
            if (!empty($_SESSION['pelanggan_id']) && (!$punyaAlamatTersimpan || ($modeAlamat === 'lain' && $simpanAlamat))) {
                $stmtAlamat = $pdo->prepare('UPDATE pelanggan SET alamat = ? WHERE id = ?');
                $stmtAlamat->execute([$alamat, $_SESSION['pelanggan_id']]);
            }

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
    <form class="contact-form" method="post" action="checkout.php" novalidate id="form-checkout">
      <label>Nama Penerima
        <input type="text" name="nama" value="<?= h($_POST['nama'] ?? $pelangganData['nama'] ?? '') ?>" required>
      </label>
      <label>Nomor Telepon / WhatsApp
        <input type="text" name="telepon" value="<?= h($_POST['telepon'] ?? $pelangganData['no_hp'] ?? '') ?>" required>
      </label>
      <label>Email (opsional)
        <input type="email" name="email" value="<?= h($_POST['email'] ?? $pelangganData['email'] ?? '') ?>">
      </label>
      <?php if ($punyaAlamatTersimpan): ?>
        <div class="alamat-pilihan">
          <span class="alamat-pilihan-judul">Alamat Pengiriman</span>
          <label class="alamat-radio">
            <input type="radio" name="mode_alamat" value="tersimpan" <?= $modeAlamat === 'tersimpan' ? 'checked' : '' ?>>
            <span>Gunakan alamat tersimpan</span>
          </label>
          <div class="alamat-tersimpan" id="boxAlamatTersimpan"><?= nl2br(h($alamatTersimpan)) ?></div>
          <label class="alamat-radio">
            <input type="radio" name="mode_alamat" value="lain" <?= $modeAlamat === 'lain' ? 'checked' : '' ?>>
            <span>Gunakan alamat lain</span>
          </label>
        </div>
      <?php endif; ?>
      <div id="wrapAlamatBaru" <?= $modeAlamat === 'tersimpan' ? 'style="display:none;"' : '' ?>>
        <label>Alamat Pengiriman<?= $punyaAlamatTersimpan ? ' Baru' : '' ?>
          <textarea name="alamat" id="inputAlamat" rows="3" <?= $modeAlamat === 'lain' ? 'required' : '' ?>><?= h($_POST['alamat'] ?? ($punyaAlamatTersimpan ? '' : ($pelangganData['alamat'] ?? ''))) ?></textarea>
        </label>
        <?php if ($punyaAlamatTersimpan): ?>
          <label class="alamat-radio alamat-simpan">
            <input type="checkbox" name="simpan_alamat" value="1" <?= !empty($_POST['simpan_alamat']) ? 'checked' : '' ?>>
            <span>Jadikan alamat tersimpan di akun</span>
          </label>
        <?php elseif ($pelangganData): ?>
          <p class="checkout-note" style="margin-top:0.5rem;">Alamat ini otomatis tersimpan ke akun kamu buat belanja berikutnya.</p>
        <?php endif; ?>
      </div>
      <label>Catatan (opsional)
        <textarea name="catatan" rows="2"><?= h($_POST['catatan'] ?? '') ?></textarea>
      </label>
       <label>Metode Pembayaran
        <select name="pembayaran" id="selectPembayaran" required>
          <option value="" disabled <?= empty($_POST['pembayaran']) ? 'selected' : '' ?>>Pilih metode pembayaran</option>
          <?php foreach ($statusPembayaranOptions as $opsi): ?>
            <option value="<?= h($opsi) ?>" <?= ($_POST['pembayaran'] ?? '') === $opsi ? 'selected' : '' ?>><?= h($opsi) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label id="wrapBank" style="display:none;">Pilih Bank Tujuan
        <select name="bank_pilihan" id="selectBank">
          <option value="" disabled <?= empty($_POST['bank_pilihan']) ? 'selected' : '' ?>>Pilih bank</option>
          <?php foreach ($bankOptions as $opsi): ?>
            <option value="<?= h($opsi) ?>" <?= ($_POST['bank_pilihan'] ?? '') === $opsi ? 'selected' : '' ?>><?= h($opsi) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label id="wrapEwallet" style="display:none;">Pilih E-Wallet
        <select name="ewallet_pilihan" id="selectEwallet">
          <option value="" disabled <?= empty($_POST['ewallet_pilihan']) ? 'selected' : '' ?>>Pilih e-wallet</option>
          <?php foreach ($ewalletOptions as $opsi): ?>
            <option value="<?= h($opsi) ?>" <?= ($_POST['ewallet_pilihan'] ?? '') === $opsi ? 'selected' : '' ?>><?= h($opsi) ?></option>
          <?php endforeach; ?>
        </select>
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

<div class="confirm-overlay" id="confirmCheckout">
  <div class="confirm-box">
    <h3>Buat pesanan ini?</h3>
    <p>Pastikan alamat dan data penerima sudah benar sebelum melanjutkan.</p>
    <div class="confirm-box-actions">
      <button type="button" class="confirm-btn-cancel" id="confirmCheckoutCancel">Batal</button>
      <button type="button" class="confirm-btn-ok" id="confirmCheckoutOk">Ya, Buat Pesanan</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>