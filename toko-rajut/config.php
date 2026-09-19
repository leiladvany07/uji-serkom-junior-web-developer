<?php

define('DB_HOST', getenv('PGHOST') ?: 'localhost');
define('DB_PORT', getenv('PGPORT') ?: '5432');
define('DB_NAME', getenv('PGDATABASE') ?: 'toko_rajutt');
define('DB_USER', getenv('PGUSER') ?: 'postgres');
define('DB_PASS', getenv('PGPASSWORD') ?: 'postgres');
define('BASE_URL', '');

try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan PostgreSQL berjalan, database "toko_rajutt" sudah dibuat di pgAdmin, dan db/rajut_postgres.sql sudah dijalankan di dalamnya. Detail: ' . $e->getMessage());
}

function format_rupiah($angka) {
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

function h($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

function first_image($gambar) {
    $parts = array_filter(array_map('trim', explode(',', (string) $gambar)));
    return $parts ? reset($parts) : 'produk-1.svg';
}

function parse_warna($warna) {
    return array_values(array_filter(array_map('trim', explode(',', (string) $warna)), fn($w) => $w !== ''));
}

// Cek apakah suatu teks status berarti "dibatalkan" (cocok dengan kata
// kunci apa pun, bukan nilai tetap, supaya fleksibel sesuai istilah yang dipakai).
function is_status_batal($status) {
    return str_contains(mb_strtolower((string) $status), 'batal');
}

// Sinkronkan stok produk saat status pesanan berubah:
// - status baru "dibatalkan" & belum pernah dikembalikan -> stok dikembalikan (+jumlah)
// - status baru BUKAN "dibatalkan" tapi sebelumnya sudah dikembalikan -> stok dipotong lagi (-jumlah)
// Dipanggil setiap kali status pesanan disimpan, dari admin/pesanan.php dan admin/pesanan_detail.php.
function sinkron_stok_pesanan(PDO $pdo, int $transaksiId, string $statusBaru) {
    $stmt = $pdo->prepare('SELECT stok_dikembalikan FROM transaksi WHERE id = ?');
    $stmt->execute([$transaksiId]);
    $sudahDikembalikan = (bool) $stmt->fetchColumn();
    $batalSekarang = is_status_batal($statusBaru);

    if ($batalSekarang && !$sudahDikembalikan) {
        $items = $pdo->prepare('SELECT produk_id, jumlah FROM transaksi_item WHERE transaksi_id = ?');
        $items->execute([$transaksiId]);
        $upd = $pdo->prepare('UPDATE produk SET stok = stok + ? WHERE id = ?');
        foreach ($items->fetchAll() as $it) {
            if (!empty($it['produk_id'])) $upd->execute([$it['jumlah'], $it['produk_id']]);
        }
        $pdo->prepare('UPDATE transaksi SET stok_dikembalikan = TRUE WHERE id = ?')->execute([$transaksiId]);
    } elseif (!$batalSekarang && $sudahDikembalikan) {
        $items = $pdo->prepare('SELECT produk_id, jumlah FROM transaksi_item WHERE transaksi_id = ?');
        $items->execute([$transaksiId]);
        $upd = $pdo->prepare('UPDATE produk SET stok = GREATEST(stok - ?, 0) WHERE id = ?');
        foreach ($items->fetchAll() as $it) {
            if (!empty($it['produk_id'])) $upd->execute([$it['jumlah'], $it['produk_id']]);
        }
        $pdo->prepare('UPDATE transaksi SET stok_dikembalikan = FALSE WHERE id = ?')->execute([$transaksiId]);
    }
}

function kategori_icon($nama) {
    $n = mb_strtolower((string) $nama);
    if (str_contains($n, 'tas') || str_contains($n, 'dompet')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="9" width="18" height="12" rx="2"/><path d="M8 9V6a4 4 0 0 1 8 0v3"/></svg>';
    }
    if (str_contains($n, 'mainan') || str_contains($n, 'boneka') || str_contains($n, 'amigurumi')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="7"/><circle cx="6" cy="6" r="2.4"/><circle cx="18" cy="6" r="2.4"/><circle cx="10" cy="12" r="0.8" fill="currentColor" stroke="none"/><circle cx="14" cy="12" r="0.8" fill="currentColor" stroke="none"/><path d="M9.5 15.5c1 1 4 1 5 0"/></svg>';
    }
    if (str_contains($n, 'topi')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13a8 8 0 0 1 16 0"/><rect x="3" y="13" width="18" height="3" rx="1.5"/><circle cx="12" cy="6" r="1.2" fill="currentColor" stroke="none"/></svg>';
    }
    if (str_contains($n, 'syal') || str_contains($n, 'scarf')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5c3 3 15 3 18 0"/><path d="M16 5c1 5-1 8 2 13"/><path d="M18 18l-2 2 2 2"/></svg>';
    }
    if (str_contains($n, 'sweater') || str_contains($n, 'cardigan') || str_contains($n, 'knit')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4l4 2 4-2 3 4-3 2v10H5V10L2 8z"/><rect x="9.5" y="2.5" width="5" height="3" rx="1"/><path d="M6 15h12M6 12h12"/></svg>';
    }
    if (str_contains($n, 'baju') || str_contains($n, 'atasan') || str_contains($n, 'blus') || str_contains($n, 'vest')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4l3 2 3-1 3 1 3-2 3 4-3 2v11H6V10L3 8z"/></svg>';
    }
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 4c2.5 2.5 2.5 13.5 0 16M12 4c-2.5 2.5-2.5 13.5 0 16M4 12h16"/></svg>';
}

function buat_slug($teks) {
    $teks = str_replace(
        ['á','à','â','ä','ã','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','ö','õ','ú','ù','û','ü','ñ','ç',
         'Á','À','Â','Ä','Ã','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Ö','Õ','Ú','Ù','Û','Ü','Ñ','Ç'],
        ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n','c',
         'a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n','c'],
        $teks
    );
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $teks), '-'));
    return $slug !== '' ? $slug : 'produk';
}