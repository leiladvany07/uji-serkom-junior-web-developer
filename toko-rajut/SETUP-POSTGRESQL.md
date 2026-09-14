# Rajutan Bu Sri — Setup dengan PostgreSQL + pgAdmin

## 1. Siapkan PostgreSQL
- Install PostgreSQL (sudah termasuk pgAdmin) dari https://www.postgresql.org/download/
- Saat instalasi, catat password untuk user `postgres`

## 2. Buat database
1. Buka pgAdmin, hubungkan ke server PostgreSQL lokal
2. Klik kanan **Databases** > **Create** > **Database**
3. Beri nama: `toko_rajut`, lalu Save

## 3. Jalankan skema
1. Klik database `toko_rajut` supaya aktif
2. Klik kanan > **Query Tool**
3. Buka file `db/rajut_postgres.sql`, salin seluruh isinya ke Query Tool
4. Jalankan (tombol Execute atau tekan F5)
5. Cek di panel kiri: harus muncul tabel `kategori`, `produk`, `pesan`, `admin`

## 4. Siapkan PHP dengan ekstensi PostgreSQL
Server PHP Anda **harus** mengaktifkan ekstensi `pdo_pgsql`. XAMPP/Laragon standar
biasanya hanya mengaktifkan `pdo_mysql`, jadi cek/aktifkan dulu:
- **XAMPP**: buka `php.ini`, hapus tanda `;` di depan baris
  `extension=pdo_pgsql` dan `extension=pgsql`, lalu restart Apache
- **Laragon**: klik menu PHP > php.ini, lakukan hal yang sama
- Atau jalankan PHP built-in server: `php -S localhost:8000` dari dalam folder `toko-rajut`

## 5. Sesuaikan config.php
Buka `config.php`, sesuaikan jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'toko_rajut');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres'); // ganti sesuai password saat instalasi
```

## 6. Jalankan
Buka `http://localhost/toko-rajut/index.php` (atau `http://localhost:8000/index.php`
jika pakai built-in server PHP).

Login admin: `http://.../admin/login.php` — username `admin`, password `admin123`
(segera ganti setelah login pertama).
