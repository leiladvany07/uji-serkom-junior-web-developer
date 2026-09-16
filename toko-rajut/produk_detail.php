<?php
require_once __DIR__ . '/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$stmt = $pdo->prepare('SELECT produk.*, kategori.nama AS kategori_nama, kategori.slug AS kategori_slug
                        FROM produk JOIN kategori ON produk.kategori_id = kategori.id
                        WHERE produk.slug = ?');
$stmt->execute([$slug]);
$produk = $stmt->fetch();

if (!$produk) {
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><p class="empty-state">Produk tidak ditemukan. <a href="produk.php">Kembali ke katalog</a>.</p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmtTerkait = $pdo->prepare('SELECT * FROM produk WHERE kategori_id = ? AND id != ? LIMIT 3');
$stmtTerkait->execute([$produk['kategori_id'], $produk['id']]);
$produkTerkait = $stmtTerkait->fetchAll();

$page_title = $produk['nama'];
require __DIR__ . '/includes/header.php';
?>

<nav class="breadcrumb">
  <a href="index.php">Beranda</a> /
  <a href="produk.php?kategori=<?= h($produk['kategori_slug']) ?>"><?= h($produk['kategori_nama']) ?></a> /
  <span><?= h($produk['nama']) ?></span>
</nav>

<section class="product-detail">
  <?php $gambarList = array_filter(array_map('trim', explode(',', $produk['gambar']))); ?>
  <div class="product-slider" data-slider>
    <div class="slider-track">
      <?php foreach ($gambarList as $i => $g): ?>
        <div class="slide"><img src="assets/<?= h($g) ?>" alt="<?= h($produk['nama']) ?> foto <?= $i + 1 ?>"></div>
      <?php endforeach; ?>
    </div>
    <?php if (count($gambarList) > 1): ?>
      <button type="button" class="slider-btn slider-prev" data-slider-prev aria-label="Foto sebelumnya">&#8249;</button>
      <button type="button" class="slider-btn slider-next" data-slider-next aria-label="Foto berikutnya">&#8250;</button>
      <div class="slider-dots" data-slider-dots>
        <?php foreach ($gambarList as $i => $g): ?>
          <button type="button" class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-slider-dot="<?= $i ?>" aria-label="Foto <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="product-detail-info">
    <p class="product-kategori"><?= h($produk['kategori_nama']) ?></p>
    <h1 class="product-detail-nama"><?= h($produk['nama']) ?></h1>
    <p class="product-detail-harga"><?= format_rupiah($produk['harga']) ?></p>
    <p class="product-detail-desc"><?= nl2br(h($produk['deskripsi'])) ?></p>
    <dl class="product-specs">
      <?php $warnaList = parse_warna($produk['warna']); ?>
      <?php if (count($warnaList) <= 1): ?>
        <div><dt>Warna</dt><dd><?= h($produk['warna']) ?></dd></div>
      <?php endif; ?>
      <div><dt>Stok</dt><dd><?= (int) $produk['stok'] ?> pcs</dd></div>
    </dl>

    <?php if ((int) $produk['stok'] > 0): ?>
      <?php if (count($warnaList) > 1): ?>
        <div class="produk-warna-pilih">
          <span class="produk-warna-label">Pilih warna</span>
          <div class="produk-warna-opsi">
            <?php foreach ($warnaList as $i => $w): ?>
              <label class="warna-chip">
                <input type="radio" name="warna" value="<?= h($w) ?>" form="form-tambah-keranjang" <?= $i === 0 ? 'checked' : '' ?> required>
                <span><?= h($w) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" action="keranjang.php" class="produk-beli-form" id="form-tambah-keranjang">
        <input type="hidden" name="tambah_id" value="<?= (int) $produk['id'] ?>">
        <input type="hidden" name="kembali" value="produk_detail.php?slug=<?= h($produk['slug']) ?>&ditambahkan=1">
        <?php if (count($warnaList) <= 1): ?>
          <input type="hidden" name="warna" value="<?= h($warnaList[0] ?? '') ?>">
        <?php endif; ?>
        <div class="produk-beli-qty">
          <label for="jumlah-beli">Jumlah</label>
          <input type="number" id="jumlah-beli" name="jumlah" value="1" min="1" max="<?= (int) $produk['stok'] ?>" form="form-tambah-keranjang">
        </div>
        <button type="submit" class="btn btn-primary">Tambah ke Keranjang</button>
      </form>

      <form method="get" action="checkout.php" class="produk-beli-langsung">
        <input type="hidden" name="beli_id" value="<?= (int) $produk['id'] ?>">
        <?php if (count($warnaList) <= 1): ?>
          <input type="hidden" name="beli_warna" value="<?= h($warnaList[0] ?? '') ?>">
        <?php else: ?>
          <input type="hidden" name="beli_warna" value="<?= h($warnaList[0]) ?>" id="beli-warna-hidden">
        <?php endif; ?>
        <input type="hidden" name="beli_jumlah" value="1" id="beli-jumlah-hidden">
        <button type="submit" class="btn btn-outline">Beli Sekarang</button>
      </form>
    <?php else: ?>
      <p class="produk-stok-habis">Stok produk ini sedang habis.</p>
    <?php endif; ?>

    <a href="https://wa.me/6288989505932?text=Halo%2C%20saya%20tertarik%20dengan%20produk%20<?= urlencode($produk['nama']) ?>" class="btn btn-outline produk-wa-btn" target="_blank" rel="noopener">Tanya via WhatsApp</a>
  </div>
</section>

<script>
(function(){
  var jumlahInput = document.getElementById('jumlah-beli');
  var beliJumlahHidden = document.getElementById('beli-jumlah-hidden');
  if (jumlahInput && beliJumlahHidden) {
    jumlahInput.addEventListener('input', function(){
      beliJumlahHidden.value = jumlahInput.value || 1;
    });
  }
  var warnaRadios = document.querySelectorAll('input[name="warna"]');
  var beliWarnaHidden = document.getElementById('beli-warna-hidden');
  if (warnaRadios.length && beliWarnaHidden) {
    warnaRadios.forEach(function(r){
      r.addEventListener('change', function(){
        if (r.checked) beliWarnaHidden.value = r.value;
      });
    });
  }
})();
</script>

<?php if (isset($_GET['ditambahkan'])): ?>
<div class="toast-notif" id="toastNotif">Produk ditambahkan ke keranjang.</div>
<script>
(function(){
  var toast = document.getElementById('toastNotif');
  if (!toast) return;
  setTimeout(function(){ toast.classList.add('show'); }, 10);
  setTimeout(function(){
    toast.classList.remove('show');
    setTimeout(function(){ toast.remove(); }, 300);
  }, 2500);
  if (window.history.replaceState) {
    var url = new URL(window.location.href);
    url.searchParams.delete('ditambahkan');
    window.history.replaceState({}, '', url);
  }
})();
</script>
<?php endif; ?>

<?php if (!empty($produkTerkait)): ?>
<section class="section">
  <h2 class="section-title">Produk sejenis</h2>
  <div class="product-grid">
    <?php foreach ($produkTerkait as $p): ?>
      <article class="product-card">
        <a href="produk_detail.php?slug=<?= h($p['slug']) ?>" class="product-thumb">
          <img src="assets/<?= h(first_image($p['gambar'])) ?>" alt="<?= h($p['nama']) ?>" loading="lazy">
        </a>
        <div class="product-body">
          <h3 class="product-nama"><a href="produk_detail.php?slug=<?= h($p['slug']) ?>"><?= h($p['nama']) ?></a></h3>
          <p class="product-harga"><?= format_rupiah($p['harga']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>