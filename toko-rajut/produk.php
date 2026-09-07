<?php
require_once __DIR__ . '/config.php';

$kategoriSlug = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$kata = isset($_GET['q']) ? trim($_GET['q']) : '';

$kategoriAktif = null;
if ($kategoriSlug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM kategori WHERE slug = ?');
    $stmt->execute([$kategoriSlug]);
    $kategoriAktif = $stmt->fetch();
}

$sql = 'SELECT produk.*, kategori.nama AS kategori_nama, kategori.slug AS kategori_slug
        FROM produk JOIN kategori ON produk.kategori_id = kategori.id WHERE 1=1';
$params = [];

if ($kategoriAktif) {
    $sql .= ' AND kategori.id = ?';
    $params[] = $kategoriAktif['id'];
}
if ($kata !== '') {
    $sql .= ' AND produk.nama ILIKE ?';
    $params[] = '%' . $kata . '%';
}
$sql .= ' ORDER BY produk.dibuat_pada DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

$page_title = $kategoriAktif ? $kategoriAktif['nama'] : 'Semua Produk';
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:2.5rem;">
  <h2 class="section-title">
    <?= $kategoriAktif ? h($kategoriAktif['nama']) : ($kata !== '' ? 'Hasil pencarian "' . h($kata) . '"' : 'Semua Produk') ?>
  </h2>

  <form class="search-form" action="produk.php" method="get" style="margin-bottom:1.6rem;">
    <?php if ($kategoriAktif): ?>
      <input type="hidden" name="kategori" value="<?= h($kategoriAktif['slug']) ?>">
    <?php endif; ?>
    <input type="text" name="q" placeholder="Cari produk, misalnya: sweater" value="<?= h($kata) ?>" required>
    <button type="submit">Cari</button>
  </form>

  <div class="category-strip">
    <a href="produk.php" class="chip <?= !$kategoriAktif ? 'chip-active' : '' ?>">Semua produk</a>
    <?php foreach ($kategoriNav as $k): ?>
      <a href="produk.php?kategori=<?= h($k['slug']) ?>" class="chip <?= ($kategoriAktif && $kategoriAktif['slug'] === $k['slug']) ? 'chip-active' : '' ?>"><?= h($k['nama']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($produkList)): ?>
    <p class="empty-state">Belum ada produk yang cocok. Coba kategori atau kata kunci lain.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($produkList as $p): ?>
        <article class="product-card">
          <a href="produk_detail.php?slug=<?= h($p['slug']) ?>" class="product-thumb">
            <img src="assets/<?= h(first_image($p['gambar'])) ?>" alt="<?= h($p['nama']) ?>" loading="lazy">
          </a>
          <div class="product-body">
            <p class="product-kategori"><?= h($p['kategori_nama']) ?></p>
            <h3 class="product-nama"><a href="produk_detail.php?slug=<?= h($p['slug']) ?>"><?= h($p['nama']) ?></a></h3>
            <p class="product-harga"><?= format_rupiah($p['harga']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>