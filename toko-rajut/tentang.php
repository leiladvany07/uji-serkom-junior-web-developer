<?php
require_once __DIR__ . '/config.php';

$page_title = 'Tentang Kami';
require __DIR__ . '/includes/header.php';
?>

<section class="section about-split" style="padding-top:2.5rem;">
  <div>
    <h1 class="section-title">Tentang Laluna Co.</h1>
    <p class="about-text">
      Laluna Co. lahir dari kecintaan pada kerajinan rajut tradisional. Setiap helai benang dirajut manual oleh pengrajin lokal, tanpa mesin, sehingga setiap produk punya karakter dan kehangatannya sendiri. Kami percaya barang yang dibuat pelan-pelan akan dipakai lebih lama.
    </p>
    <p class="about-text" style="margin-top:1rem;">
      Berawal dari hobi merajut di rumah, Laluna Co. kini berkembang menjadi usaha kecil yang melayani pelanggan dari berbagai kota, dengan tetap mempertahankan proses pembuatan yang manual dan penuh perhatian pada detail.
    </p>
  </div>
  <img src="assets/rajut.png" alt="Proses merajut tangan Laluna Co." class="about-image">
</section>

<section class="section about-split about-split-reverse" id="proses">
  <div class="about-image-stack">
    <img src="assets/benang.png" alt="Benang rajut pilihan Laluna Co." class="about-image about-image-square">
    <img src="assets/packing.png" alt="Pengemasan produk Laluna Co." class="about-image about-image-square">
  </div>
  <div class="about-split-col">
    <div class="quote-brand">
      <p class="quote-brand-text">&ldquo;Dirajut perlahan, dibuat dengan hati.&rdquo;</p>
      <p class="quote-brand-desc">Setiap karya Lalunaco melewati proses manual yang penuh ketelitian untuk menjaga kualitas dan karakter unik di setiap rajutan.</p>
    </div>
    <div>
      <h2 class="section-title" style="margin-top:2rem;">Proses Pembuatan</h2>
      <div class="about-points">
        <div class="about-point">
          <span class="point-icon">01</span>
          <p class="point-title">Pemilihan Benang</p>
          <p class="point-desc">Kami memilih benang wol dan katun campur berkualitas, nyaman dipakai dan tahan lama.</p>
        </div>
        <div class="about-point">
          <span class="point-icon">02</span>
          <p class="point-title">Merajut Manual</p>
          <p class="point-desc">Setiap produk dirajut satu per satu oleh pengrajin, tanpa bantuan mesin, sehingga tiap potongan punya karakter unik.</p>
        </div>
        <div class="about-point">
          <span class="point-icon">03</span>
          <p class="point-title">Quality Check &amp; Packing</p>
          <p class="point-desc">Setiap produk diperiksa kerapian jahitannya, lalu dikemas rapi sebelum dikirim ke pelanggan.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section about-split" id="keunggulan">
  <div>
    <h2 class="section-title">Keunggulan</h2>
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
  <img src="assets/display.png" alt="Koleksi tas rajut Laluna Co." class="about-image">
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>