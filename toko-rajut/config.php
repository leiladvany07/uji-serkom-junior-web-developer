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