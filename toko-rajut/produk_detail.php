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
    echo '<section class="section"><p class="empty-state">Produk tidak ditemukan. <a href="index.php">Kembali ke katalog</a>.</p></section>';
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
  <a href="index.php?kategori=<?= h($produk['kategori_slug']) ?>"><?= h($produk['kategori_nama']) ?></a> /
  <span><?= h($produk['nama']) ?></span>
</nav>

<section class="product-detail">
  <div class="product-detail-image">
    <img src="assets/<?= h($produk['gambar']) ?>" alt="<?= h($produk['nama']) ?>">
  </div>
  <div class="product-detail-info">
    <p class="product-kategori"><?= h($produk['kategori_nama']) ?></p>
    <h1 class="product-detail-nama"><?= h($produk['nama']) ?></h1>
    <p class="product-detail-harga"><?= format_rupiah($produk['harga']) ?></p>
    <p class="product-detail-desc"><?= nl2br(h($produk['deskripsi'])) ?></p>
    <dl class="product-specs">
      <div><dt>Warna</dt><dd><?= h($produk['warna']) ?></dd></div>
      <div><dt>Stok</dt><dd><?= (int) $produk['stok'] ?> pcs</dd></div>
    </dl>
    <a href="https://wa.me/6281234567890?text=Halo%2C%20saya%20tertarik%20dengan%20produk%20<?= urlencode($produk['nama']) ?>" class="btn btn-primary" target="_blank" rel="noopener">Tanya via WhatsApp</a>
  </div>
</section>

<?php if (!empty($produkTerkait)): ?>
<section class="section">
  <h2 class="section-title">Produk sejenis</h2>
  <div class="product-grid">
    <?php foreach ($produkTerkait as $p): ?>
      <article class="product-card">
        <a href="produk_detail.php?slug=<?= h($p['slug']) ?>" class="product-thumb">
          <img src="assets/<?= h($p['gambar']) ?>" alt="<?= h($p['nama']) ?>" loading="lazy">
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
