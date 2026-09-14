-- Skema database Toko Rajut "Rajutan Bu Sri" (PostgreSQL)
--
-- Cara pakai di pgAdmin:
-- 1. Buat database baru bernama "toko_rajut" (klik kanan Databases > Create > Database)
-- 2. Klik database "toko_rajut" tersebut supaya aktif/terhubung
-- 3. Buka Query Tool (klik kanan database > Query Tool)
-- 4. Tempel seluruh isi file ini lalu jalankan (Execute / F5)

CREATE TABLE kategori (
  id SERIAL PRIMARY KEY,
  nama VARCHAR(60) NOT NULL,
  slug VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE produk (
  id SERIAL PRIMARY KEY,
  kategori_id INT NOT NULL REFERENCES kategori(id) ON DELETE CASCADE,
  nama VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  deskripsi TEXT,
  harga NUMERIC(10,0) NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  warna VARCHAR(80),
  gambar VARCHAR(255),
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pesan (
  id SERIAL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL,
  telepon VARCHAR(30),
  isi_pesan TEXT NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);

-- Akun admin default: username "admin", password "admin123"
-- GANTI password ini setelah login pertama kali.
INSERT INTO admin (username, password_hash) VALUES
('admin', '$2b$12$6Lb7ILWpMCaDYtatKAFlruqeYmat59/1YuPOl84ngBJFoDAtyPfpq');

INSERT INTO kategori (nama, slug) VALUES
('Baju Rajut', 'baju-rajut'),
('Sweater Rajut', 'sweater-rajut'),
('Tas Rajut', 'tas-rajut'),
('Mainan Rajut', 'mainan-rajut');

INSERT INTO produk (kategori_id, nama, slug, deskripsi, harga, stok, warna, gambar) VALUES
(1, 'Cardigan Rajut Wol Krem', 'cardigan-rajut-wol-krem', 'Cardigan rajutan tangan berbahan wol lembut, cocok dipakai sehari-hari maupun untuk acara santai. Setiap helai dirajut manual sehingga tekstur dan kerapatannya khas buatan tangan.', 185000, 12, 'Krem', 'produk-1.svg'),
(1, 'Blus Rajut Motif Kabel', 'blus-rajut-motif-kabel', 'Blus rajut dengan motif kabel klasik di bagian depan, dibuat dari benang katun campur agar tetap nyaman dipakai di cuaca hangat.', 165000, 8, 'Putih Gading', 'produk-2.svg'),
(2, 'Sweater Rajut Turtleneck', 'sweater-rajut-turtleneck', 'Sweater leher tinggi dengan rajutan rapat untuk kehangatan ekstra, pilihan tepat untuk musim hujan atau dataran tinggi.', 210000, 10, 'Coklat Tua', 'produk-3.svg'),
(2, 'Sweater Rajut Oversize Rajut Kasar', 'sweater-oversize-rajut-kasar', 'Sweater oversize dengan rajutan kasar bertekstur chunky knit, memberikan kesan santai namun tetap modis.', 235000, 6, 'Abu Tua', 'produk-4.svg'),
(3, 'Tas Rajut Tote Serut', 'tas-rajut-tote-serut', 'Tas tote rajutan tangan dengan tali serut, dilapisi kain furing di bagian dalam agar barang bawaan lebih aman.', 95000, 15, 'Natural', 'produk-5.svg'),
(3, 'Tas Selempang Rajut Mini', 'tas-selempang-rajut-mini', 'Tas selempang mini hasil rajutan rapat, pas untuk membawa dompet dan ponsel saat bepergian ringan.', 78000, 20, 'Terracotta', 'produk-6.svg'),
(4, 'Boneka Rajut Kelinci', 'boneka-rajut-kelinci', 'Boneka rajut berbentuk kelinci dengan isian dakron lembut, aman untuk anak-anak dan cocok sebagai hadiah.', 55000, 25, 'Pink Pastel', 'produk-7.svg'),
(4, 'Gantungan Kunci Rajut Amigurumi', 'gantungan-kunci-rajut-amigurumi', 'Gantungan kunci rajutan amigurumi mini dengan berbagai bentuk karakter, dibuat dengan detail yang presisi.', 20000, 40, 'Beragam', 'produk-8.svg');
