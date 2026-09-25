<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifikasi.php';
require_login();

// ============================================================
// Daftar status TETAP (baru) — selalu muncul di dropdown, tidak
// lagi bergantung status apa yang kebetulan sudah pernah dipakai.
// Kalau ada status lama di data yang tidak ada di daftar ini,
// tetap ditambahkan di bawahnya supaya data lama tidak hilang.
// ============================================================
$statusTetap = ['menunggu konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
$statusDariData = $pdo->query('SELECT DISTINCT status FROM transaksi WHERE status IS NOT NULL AND status <> \'\' ORDER BY status')->fetchAll(PDO::FETCH_COLUMN);
$statusOptions = array_values(array_unique(array_merge($statusTetap, $statusDariData)));

function warna_status_pesanan($status) {
    $s = mb_strtolower((string) $status);
    if ($s === '') return 'status-new';
    if (str_contains($s, 'batal') || str_contains($s, 'tolak')) return 'status-danger';
    if (str_contains($s, 'selesai') || str_contains($s, 'kirim') || str_contains($s, 'sukses')) return 'status-done';
    if (str_contains($s, 'proses') || str_contains($s, 'konfirmasi') || str_contains($s, 'bayar')) return 'status-progress';
    return 'status-new';
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM transaksi WHERE id = ?');
$stmt->execute([$id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    header('Location: pesanan.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengiriman'])) {
    // Dropdown mengirim "ekspedisi_pilih"; kalau nilainya "__lainnya__", nama ekspedisi
    // sebenarnya ada di "ekspedisi_lainnya" (kolom teks yang muncul saat itu dipilih).
    $ekspedisiPilih = trim($_POST['ekspedisi_pilih'] ?? '');
    $ekspedisi = ($ekspedisiPilih === '__lainnya__')
        ? trim($_POST['ekspedisi_lainnya'] ?? '')
        : $ekspedisiPilih;
    $noResi = trim($_POST['no_resi'] ?? '');
    $pdo->prepare('UPDATE transaksi SET ekspedisi = ?, no_resi = ? WHERE id = ?')
        ->execute([$ekspedisi ?: null, $noResi ?: null, $id]);
    header('Location: pesanan_detail.php?id=' . $id . '&pengiriman_updated=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
    $new = trim($_POST['set_status']);
    if ($new !== '') {
        $pdo->prepare('UPDATE transaksi SET status = ? WHERE id = ?')->execute([$new, $id]);
        sinkron_stok_pesanan($pdo, $id, $new);

        // ===== Notifikasi ke pembeli lewat WhatsApp (tautan siap kirim) =====
        $waLink = link_wa_notifikasi_status($transaksi['telepon'] ?? '', $transaksi['nama'], $transaksi['kode'], $new);

        $redirectQuery = http_build_query([
            'id'             => $id,
            'status_updated' => 1,
            'wa_link'        => $waLink,
        ]);
        header('Location: pesanan_detail.php?' . $redirectQuery);
        exit;
    }
    header('Location: pesanan_detail.php?id=' . $id);
    exit;
}

$stmtItem = $pdo->prepare("SELECT ti.*, p.gambar, p.slug FROM transaksi_item ti LEFT JOIN produk p ON p.id = ti.produk_id WHERE ti.transaksi_id = ? ORDER BY ti.id");
$stmtItem->execute([$id]);
$itemList = $stmtItem->fetchAll();

$st = $transaksi['status'] ?: 'menunggu konfirmasi';
$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();
$totalBaruPesan = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan <?= h($transaksi['kode']) ?> — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
  /* ===== Banner notifikasi status (baru) ===== */
  .pesanan-notif-banner {
    background: #eef8f0;
    border: 1px solid #bfe3c7;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem 1.25rem;
    justify-content: space-between;
  }
  .pesanan-notif-banner.is-error {
    background: #fdf1f1;
    border-color: #f0c2c2;
  }
  .pesanan-notif-banner p { margin: 0; font-size: .92rem; }
  .pesanan-notif-banner code {
    background: rgba(0,0,0,0.06);
    padding: .1rem .35rem;
    border-radius: 4px;
    font-size: .85em;
  }
</style>
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
      <p class="logo">Laluna<span>co.</span></p>
      <p class="admin-sidebar-tagline">handmade knitwear</p>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        <span>Produk</span>
      </a>
      <a href="kategori.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.82 0l4.6-4.6a2 2 0 0 0 0-2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
        <span>Kategori</span>
      </a>
      <a href="pesanan.php" class="admin-nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span>Pesanan</span>
        <?php if ($totalBaruPesanan > 0): ?><span class="admin-nav-badge"><?= $totalBaruPesanan ?></span><?php endif; ?>
      </a>
      <a href="laporan.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Laporan</span>
      </a>
      <a href="pesan.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        <span>Pesan masuk</span>
        <?php if ($totalBaruPesan > 0): ?><span class="admin-nav-badge"><?= $totalBaruPesan ?></span><?php endif; ?>
      </a>
      <a href="../index.php" target="_blank" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <span>Lihat toko</span>
      </a>
      <a href="logout.php" class="admin-nav-item" id="logoutLink">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Keluar</span>
      </a>
    </nav>
    <p class="admin-sidebar-footer">Handmade with love &hearts;</p>
  </aside>

  <main class="admin-main">
    <a href="pesanan.php" class="admin-back">&larr; Kembali ke daftar pesanan</a>

    <?php if (isset($_GET['pengiriman_updated']) && $_GET['pengiriman_updated'] == '1'): ?>
      <div class="pesanan-notif-banner">
        <p>Info ekspedisi &amp; nomor resi berhasil disimpan.</p>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == '1'): ?>
      <div class="pesanan-notif-banner">
        <p>Status pesanan berhasil diperbarui.</p>
        <?php if (!empty($_GET['wa_link'])): ?>
          <a class="icon-btn icon-btn-wa" href="<?= h($_GET['wa_link']) ?>" target="_blank" rel="noopener">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.3A8.9 8.9 0 0 0 12 4a8.9 8.9 0 0 0-7.8 13.4L3 21l3.7-1.2A8.9 8.9 0 0 0 12 21a8.9 8.9 0 0 0 5.6-15.7zM12 19.3a7.3 7.3 0 0 1-3.9-1.1l-.3-.2-2.6.9.8-2.5-.2-.3A7.3 7.3 0 1 1 19.3 12 7.3 7.3 0 0 1 12 19.3z"/></svg>
            <span>Kirim notifikasi WhatsApp ke pembeli</span>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="pesanan-detail-hero">
      <div>
        <p class="pesanan-detail-eyebrow">Kode pesanan</p>
        <h1 class="pesanan-detail-kode"><?= h($transaksi['kode']) ?></h1>
        <p class="pesanan-detail-tanggal"><?= h(date('d M Y, H:i', strtotime($transaksi['dibuat_pada']))) ?></p>
      </div>
      <span class="status-badge status-badge-lg <?= warna_status_pesanan($st) ?>">
        <span class="status-dot"></span><?= h(ucwords($st)) ?>
      </span>
    </div>

    <div class="pesanan-main-card">
      <div class="pesanan-main-grid">
        <div class="pesanan-detail-items">
          <h2 class="pesanan-detail-subheading">Item Pesanan</h2>
          <?php foreach ($itemList as $it): ?>
            <div class="pesanan-item-row">
              <div class="pesanan-item-thumb">
                <?php if (!empty($it['gambar'])): ?>
                  <img src="../assets/<?= h(first_image($it['gambar'])) ?>" alt="<?= h($it['nama_produk']) ?>">
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
          <h2 class="pesanan-detail-subheading">Data Pembeli</h2>
          <div class="contact-info">
            <div class="contact-item">
              <span class="contact-label">Nama Penerima</span>
              <span class="contact-value"><?= h($transaksi['nama']) ?></span>
            </div>
            <div class="contact-item">
              <span class="contact-label">Telepon / WhatsApp</span>
              <span class="contact-value"><?= h($transaksi['telepon']) ?></span>
            </div>
            <?php if (!empty($transaksi['email'])): ?>
            <div class="contact-item">
              <span class="contact-label">Email</span>
              <span class="contact-value"><?= h($transaksi['email']) ?></span>
            </div>
            <?php endif; ?>
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
            <?php if (!empty($transaksi['catatan'])): ?>
            <div class="contact-item">
              <span class="contact-label">Catatan</span>
              <span class="contact-value"><?= nl2br(h($transaksi['catatan'])) ?></span>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($transaksi['telepon'])): ?>
            <a class="icon-btn icon-btn-wa icon-btn-block" href="https://wa.me/<?= h(normalisasi_nomor_wa($transaksi['telepon'])) ?>" target="_blank" rel="noopener">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.3A8.9 8.9 0 0 0 12 4a8.9 8.9 0 0 0-7.8 13.4L3 21l3.7-1.2A8.9 8.9 0 0 0 12 21a8.9 8.9 0 0 0 5.6-15.7zM12 19.3a7.3 7.3 0 0 1-3.9-1.1l-.3-.2-2.6.9.8-2.5-.2-.3A7.3 7.3 0 1 1 19.3 12 7.3 7.3 0 0 1 12 19.3z"/></svg>
              <span>Chat via WhatsApp</span>
            </a>
          <?php endif; ?>
          <a class="icon-btn icon-btn-block" href="../invoice.php?kode=<?= urlencode($transaksi['kode']) ?>&amp;dari=admin" target="_blank">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Invoice</span>
          </a>
        </div>
      </div>
    </div>

    <div class="pesanan-status-grid">
      <div class="pesanan-side-card">
        <div class="pesanan-side-card-heading">
          <span class="pesanan-side-card-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3.5-7.1"></path><polyline points="21 3 21 9 15 9"></polyline></svg>
          </span>
          <h2 class="pesanan-detail-subheading" style="margin-bottom:0;">Ubah Status</h2>
        </div>
        <form method="post" action="pesanan_detail.php?id=<?= $id ?>" class="pesanan-status-form">
          <select name="set_status" onchange="this.form.submit()">
            <?php foreach ($statusOptions as $opt): ?>
              <option value="<?= h($opt) ?>" <?= $opt === $st ? 'selected' : '' ?>><?= h(ucwords($opt)) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <div class="pesanan-status-divider">atau</div>
        <form method="post" action="pesanan_detail.php?id=<?= $id ?>" class="pesanan-status-form pesanan-status-manual">
          <input type="text" name="set_status" placeholder="Ketik status baru...">
          <button type="submit" class="btn-pill">Simpan</button>
        </form>
        <p class="pesanan-side-card-hint">
          Setiap kali status diubah, tombol kirim notifikasi WhatsApp ke pembeli akan muncul di atas.
        </p>
      </div>

      <div class="pesanan-side-card">
        <div class="pesanan-side-card-heading">
          <span class="pesanan-side-card-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          </span>
          <h2 class="pesanan-detail-subheading" style="margin-bottom:0;">Ekspedisi &amp; Resi</h2>
        </div>
        <?php
        $daftarEkspedisi = ['JNE', 'J&T Express', 'SiCepat', 'AnterAja', 'Ninja Express', 'ID Express', 'Pos Indonesia', 'GoSend / GrabExpress'];
        $ekspedisiTersimpan = $transaksi['ekspedisi'] ?? '';
        $noResiTersimpan = $transaksi['no_resi'] ?? '';
        $ekspedisiLainnya = $ekspedisiTersimpan !== '' && !in_array($ekspedisiTersimpan, $daftarEkspedisi, true);
        ?>
        <?php if ($ekspedisiTersimpan !== '' || $noResiTersimpan !== ''): ?>
          <div class="pengiriman-tersimpan">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span><?= $ekspedisiTersimpan !== '' ? h($ekspedisiTersimpan) : 'Ekspedisi belum diisi' ?><?= $noResiTersimpan !== '' ? ' &middot; ' . h($noResiTersimpan) : '' ?></span>
          </div>
        <?php endif; ?>
        <form method="post" action="pesanan_detail.php?id=<?= $id ?>" class="pesanan-status-form pesanan-ekspedisi-row">
          <select name="ekspedisi_pilih" id="pilihEkspedisi">
            <option value="">Pilih ekspedisi...</option>
            <?php foreach ($daftarEkspedisi as $e): ?>
              <option value="<?= h($e) ?>" <?= $ekspedisiTersimpan === $e ? 'selected' : '' ?>><?= h($e) ?></option>
            <?php endforeach; ?>
            <option value="__lainnya__" <?= $ekspedisiLainnya ? 'selected' : '' ?>>Lainnya...</option>
          </select>
          <input type="text" name="ekspedisi_lainnya" id="ekspedisiLainnya" placeholder="Nama ekspedisi lain" value="<?= $ekspedisiLainnya ? h($ekspedisiTersimpan) : '' ?>" style="<?= $ekspedisiLainnya ? '' : 'display:none;' ?>">
          <input type="text" name="no_resi" placeholder="Nomor resi" value="<?= h($noResiTersimpan) ?>">
          <button type="submit" name="simpan_pengiriman" value="1" class="btn-pill btn-pill-solid">Simpan</button>
        </form>
        <script>
        (function(){
          var sel = document.getElementById('pilihEkspedisi');
          var lain = document.getElementById('ekspedisiLainnya');
          if (!sel || !lain) return;
          function toggleLainnya(){ lain.style.display = sel.value === '__lainnya__' ? '' : 'none'; }
          sel.addEventListener('change', toggleLainnya);
          toggleLainnya();
        })();
        </script>
        <p class="pesanan-side-card-hint">
          Kalau diisi, pelanggan yang login bisa melihat info ini di halaman "Pesanan Saya".
        </p>
      </div>
    </div>
  </main>
</div>
<div class="confirm-overlay" id="confirmLogout">
  <div class="confirm-box">
    <h3>Keluar dari akun admin?</h3>
    <p>Kamu perlu login lagi buat masuk ke dashboard ini.</p>
    <div class="confirm-box-actions">
      <button type="button" class="confirm-btn-cancel" id="confirmLogoutCancel">Batal</button>
      <button type="button" class="confirm-btn-ok" id="confirmLogoutOk">Ya, Keluar</button>
    </div>
  </div>
</div>
<script>
(function(){
  var link = document.getElementById("logoutLink");
  var overlay = document.getElementById("confirmLogout");
  if (!link || !overlay) return;
  link.addEventListener("click", function(e){
    e.preventDefault();
    overlay.classList.add("open");
  });
  document.getElementById("confirmLogoutCancel").addEventListener("click", function(){
    overlay.classList.remove("open");
  });
  document.getElementById("confirmLogoutOk").addEventListener("click", function(){
    window.location.href = "logout.php";
  });
  overlay.addEventListener("click", function(e){
    if (e.target === overlay) overlay.classList.remove("open");
  });
})();
</script>
</body>
</html>