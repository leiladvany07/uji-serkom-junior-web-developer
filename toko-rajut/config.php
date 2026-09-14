<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'toko_rajutt');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres');

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
    die('Koneksi database gagal. Pastikan PostgreSQL berjalan, database "toko_rajut" sudah dibuat di pgAdmin, dan db/rajut_postgres.sql sudah dijalankan di dalamnya. Detail: ' . $e->getMessage());
}

function format_rupiah($angka) {
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

function h($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}
