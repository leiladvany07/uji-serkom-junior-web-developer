<?php
require_once __DIR__ . '/config.php';

$produkPilihan = $pdo->query('SELECT produk.*, kategori.nama AS kategori_nama
                               FROM produk JOIN kategori ON produk.kategori_id = kategori.id
                               ORDER BY produk.dibuat_pada DESC LIMIT 4')->fetchAll();

$page_title = 'Beranda';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-shop">
  <p class="hero-eyebrow reveal">Kerajinan tangan · Dibuat dengan rajutan manual</p>
  <h1 class="hero-title reveal">Rajutan hangat, dibuat satu per satu dengan tangan.</h1>
  <p class="hero-sub reveal">Setiap produk di Lalunaco dikerjakan langsung oleh pengrajin, mulai dari baju dan sweater rajut hingga tas dan mainan amigurumi.</p>
  <form class="search-form reveal" action="produk.php" method="get">
    <input type="text" name="q" placeholder="Cari produk, misalnya: sweater">
    <button type="submit">Cari</button>
  </form>
  <div class="hero-cta reveal">
    <a href="produk.php" class="btn btn-primary">Lihat Semua Produk</a>
    <a href="https://api.whatsapp.com/send/?phone=62xxxxxxxxxx&text=Halo%20Lalunaco%2C%20saya%20mau%20tanya%20produk" class="btn btn-outline" target="_blank" rel="noopener">Konsultasi via WhatsApp</a>
  </div>
</section>

<section class="about-strip">
  <div class="about-copy">
    <h2 class="section-title">Tentang Lalunaco</h2>
    <p>Lalunaco lahir dari kecintaan pada kerajinan rajut tradisional. Setiap helai benang dirajut manual oleh pengrajin lokal, tanpa mesin, sehingga setiap produk punya karakter dan kehangatannya sendiri. Kami percaya barang yang dibuat pelan-pelan akan dipakai lebih lama.</p>
  </div>
  <div class="about-points">
    <div class="about-point">
      <span class="point-icon">01</span>
      <p class="point-title">Dirajut manual</p>
      <p class="point-desc">Tanpa mesin, murni keterampilan tangan pengrajin.</p>
    </div>
    <div class="about-point">
      <span class="point-icon">02</span>
      <p class="point-title">Bahan pilihan</p>
      <p class="point-desc">Benang wol dan katun campur yang nyaman dipakai.</p>
    </div>
    <div class="about-point">
      <span class="point-icon">03</span>
      <p class="point-title">Bisa custom</p>
      <p class="point-desc">Warna dan ukuran bisa disesuaikan lewat WhatsApp.</p>
    </div>
  </div>
</section>

<section class="section testimoni-section">
  <h2 class="section-title">Cerita dari Pelanggan Lalunaco</h2>
  <div class="testimoni-grid">
    <blockquote class="testimoni-card">
      <p class="testimoni-text">"Sweater rajutnya hangat banget dan rapi jahitannya. Bikin lagi warna lain deh."</p>
      <cite class="testimoni-name">— @dinaputri</cite>
    </blockquote>
    <blockquote class="testimoni-card">
      <p class="testimoni-text">"Pesen tas rajut custom, hasilnya persis referensi yang saya kasih. Pengiriman juga cepat."</p>
      <cite class="testimoni-name">— @ratna.k</cite>
    </blockquote>
    <blockquote class="testimoni-card">
      <p class="testimoni-text">"Mainan rajut buat anak saya lembut dan aman, motifnya juga lucu-lucu."</p>
      <cite class="testimoni-name">— @bunda_alya</cite>
    </blockquote>
  </div>
</section>

<?php if (!empty($produkPilihan)): ?>
<section class="section featured-section">
  <div class="featured-heading">
    <h2 class="section-title">Produk pilihan</h2>
    <a href="produk.php" class="featured-link">Lihat semua produk &rarr;</a>
  </div>
  <div class="product-grid">
    <?php foreach ($produkPilihan as $p): ?>
      <article class="product-card">
        <a href="produk_detail.php?slug=<?= h($p['slug']) ?>" class="product-thumb">
          <img src="assets/<?= h($p['gambar']) ?>" alt="<?= h($p['nama']) ?>" loading="lazy">
        </a>
        <div class="product-body">
          <p class="product-kategori"><?= h($p['kategori_nama']) ?></p>
          <h3 class="product-nama"><a href="produk_detail.php?slug=<?= h($p['slug']) ?>"><?= h($p['nama']) ?></a></h3>
          <p class="product-harga"><?= format_rupiah($p['harga']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="category-strip">
  <a href="produk.php" class="chip">Semua produk</a>
  <?php foreach ($kategoriNav as $k): ?>
    <a href="produk.php?kategori=<?= h($k['slug']) ?>" class="chip"><?= h($k['nama']) ?></a>
  <?php endforeach; ?>
</section>

<?php if (!empty($produkPilihan)): ?>
<section class="section instagram-section">
  <h2 class="section-title">Inspirasi dari Rajutan Kami</h2>
  <div class="instagram-grid">
    <?php foreach (array_slice($produkPilihan, 0, 4) as $p): ?>
      <a href="produk_detail.php?slug=<?= h($p['slug']) ?>" class="instagram-item">
        <img src="assets/<?= h($p['gambar']) ?>" alt="<?= h($p['nama']) ?>" loading="lazy">
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section visit-section">
  <h2 class="section-title">Kunjungi Kami</h2>
  <div class="visit-layout">
    <div class="visit-info">
      <div class="contact-item">
        <span class="contact-label">Alamat</span>
        <span class="contact-value">Jl. Nusa Penida No. 27, Madiun, Jawa Timur</span>
      </div>
      <div class="contact-item">
        <span class="contact-label">Jam Buka</span>
        <span class="contact-value">08.00 – 16.30, Senin – Sabtu</span>
      </div>
      <div class="contact-item">
        <span class="contact-label">WhatsApp</span>
        <span class="contact-value">0812-0706-2020</span>
      </div>
    </div>
    <div class="visit-map">
      <iframe src="https://www.google.com/maps/embed/v1/place?key=ISI_API_KEY&q=Alamat+Toko+Anda" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>