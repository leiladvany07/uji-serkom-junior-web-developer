<?php
require_once __DIR__ . '/config.php';

$produkPilihan = $pdo->query('SELECT produk.*, kategori.nama AS kategori_nama
                               FROM produk JOIN kategori ON produk.kategori_id = kategori.id
                               ORDER BY produk.dibuat_pada DESC LIMIT 4')->fetchAll();

$page_title = 'Beranda';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-shop">
  <div class="hero-grid">
    <div class="hero-content">
      <p class="hero-eyebrow reveal">Kerajinan tangan · Dibuat dengan rajutan manual</p>
      <h1 class="hero-title reveal">Rajutan hangat, dibuat satu per-satu dengan tangan.</h1>
      <p class="hero-sub reveal">Setiap produk di Lalunaco dikerjakan langsung oleh pengrajin, mulai dari baju dan sweater rajut hingga tas dan mainan amigurumi.</p>
      <form class="search-form reveal" action="produk.php" method="get">
        <input type="text" name="q" placeholder="Cari produk, misalnya: sweater">
        <button type="submit">Cari</button>
      </form>
      <div class="hero-cta reveal">
        <a href="https://api.whatsapp.com/send/?phone=6288989505932&text=Halo%20Lalunaco%2C%20saya%20mau%20tanya%20produk" class="btn btn-primary" target="_blank" rel="noopener">Konsultasi via WhatsApp</a>
      </div>
    </div>

    <div class="hero-illustration reveal">
      <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="195" cy="330" rx="120" ry="14" fill="#33402C" opacity="0.06"/>
        <g transform="translate(180 190)">
          <circle cx="0" cy="0" r="115" fill="#A8623B"/>
          <path d="M-95 -30 C-70 -75 -20 -100 30 -95" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M-100 5 C-80 -55 -20 -90 45 -78" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M-98 40 C-85 -30 -15 -75 60 -55" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M-90 75 C-80 5 -5 -55 75 -25" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M-70 100 C-65 30 10 -30 90 5" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M-40 112 C-40 50 25 5 98 40" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M-5 115 C-5 65 45 30 105 75" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.5"/>
          <path d="M30 112 C30 78 65 55 105 105" fill="none" stroke="#7C4527" stroke-width="2.5" opacity="0.4"/>
          <ellipse cx="-40" cy="-45" rx="30" ry="18" fill="#fff" opacity="0.12"/>
          <path d="M92 55 C130 75 150 65 148 40 C146 20 165 15 175 30"
                fill="none" stroke="#7C4527" stroke-width="3" stroke-linecap="round"/>
        </g>
        <path d="M355 245 C330 270 300 260 295 290 C291 315 260 320 250 300"
              fill="none" stroke="#33402C" stroke-width="2.5" stroke-linecap="round" opacity="0.55"/>
        <g stroke="#8C6A2E" stroke-width="4" stroke-linecap="round">
          <line x1="100" y1="90" x2="55" y2="15"/>
          <line x1="260" y1="90" x2="305" y2="15"/>
        </g>
        <circle cx="55" cy="15" r="6" fill="#8C6A2E"/>
        <circle cx="305" cy="15" r="6" fill="#8C6A2E"/>
        <g fill="#33402C" opacity="0.35">
          <circle cx="70" cy="330" r="3.5"/>
          <circle cx="88" cy="330" r="3.5"/>
          <circle cx="79" cy="345" r="3.5"/>
        </g>
      </svg>
    </div>
  </div>
</section>

<?php if (!empty($produkPilihan)): ?>
<section class="section featured-section">
  <div class="featured-heading">
    <h2 class="section-title">Produk Unggulan</h2>
    <a href="produk.php" class="featured-link">Lihat semua produk &rarr;</a>
  </div>
  <div class="product-grid">
    <?php foreach ($produkPilihan as $p): ?>
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
</section>
<?php endif; ?>

<section class="about-strip about-strip-split">
  <div>
    <div class="about-copy">
      <h2 class="section-title">Tentang Lalunaco.</h2>
      <p>Laluna Co. lahir dari kecintaan pada kerajinan rajut dan proses yang dibuat dengan penuh ketelitian. Setiap karya dirajut secara manual oleh pengrajin lokal, menghadirkan karakter unik pada setiap detailnya.</p>
    </div>
    <div class="about-points">
      <div class="about-point">
        <span class="point-icon">01</span>
        <p class="point-title">Handcrafted</p>
        <p class="point-desc">Dirajut secara manual dengan ketelitian pada setiap detail.</p>
      </div>
      <div class="about-point">
        <span class="point-icon">02</span>
        <p class="point-title">Selected Materials</p>
        <p class="point-desc">Menggunakan benang pilihan yang nyaman dan berkualitas.</p>
      </div>
      <div class="about-point">
        <span class="point-icon">03</span>
        <p class="point-title">Made to Order</p>
        <p class="point-desc">Pilihan warna dan ukuran dapat disesuaikan dengan kebutuhan.</p>
      </div>
    </div>
  </div>
  <img src="assets/tentang-beranda.png" alt="Perlengkapan merajut Laluna Co." class="about-strip-image">
</section>

<section class="section testimoni-section">
  <h2 class="section-title">Cerita dari Pelanggan Lalunaco.</h2>
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

<section class="section order-steps-section">
  <h2 class="section-title">Cara Order</h2>
  <div class="order-steps">
    <div class="order-step">
      <span class="order-step-num">1</span>
      <p class="order-step-title">Pilih Produk</p>
      <p class="order-step-desc">Lihat katalog dan pilih rajutan yang kamu suka.</p>
    </div>
    <div class="order-step">
      <span class="order-step-num">2</span>
      <p class="order-step-title">Hubungi via WhatsApp</p>
      <p class="order-step-desc">Klik "Tanya via WhatsApp" di halaman produk untuk konfirmasi warna dan ukuran.</p>
    </div>
    <div class="order-step">
      <span class="order-step-num">3</span>
      <p class="order-step-title">Konfirmasi & Bayar</p>
      <p class="order-step-desc">Kami kirim rincian harga dan ongkir, lalu kamu transfer sesuai kesepakatan.</p>
    </div>
    <div class="order-step">
      <span class="order-step-num">4</span>
      <p class="order-step-title">Produk Dikirim</p>
      <p class="order-step-desc">Pesanan diproses dan dikirim ke alamatmu beserta nomor resi.</p>
    </div>
  </div>
</section>



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
        <span class="contact-value">0889-8950-5932</span>
      </div>
    </div>
    <div class="visit-map">
      <iframe src="https://www.google.com/maps?q=-7.6285134,111.5340622&z=17&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>