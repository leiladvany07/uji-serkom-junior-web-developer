<?php
require_once __DIR__ . '/auth.php';
require_login();

// Produk TIDAK PERNAH dihapus, supaya riwayat pesanan & laporan tetap utuh.
// Kalau admin mencoba menghapus, tampilkan pesan dan arahkan ke "Nonaktifkan".
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT nama FROM produk WHERE id = ?');
    $stmt->execute([$id]);
    $nama = $stmt->fetchColumn();
    if ($nama !== false) {
        $_SESSION['flash_produk_error'] = $nama . ' tidak dapat dihapus karena memiliki riwayat pesanan. Gunakan Nonaktifkan jika produk sudah tidak dijual.';
    }
}

header('Location: dashboard.php');
exit;