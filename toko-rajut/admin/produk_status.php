<?php
require_once __DIR__ . '/auth.php';
require_login();

// Produk tidak pernah dihapus (riwayat pesanan & laporan tetap utuh); hanya diaktifkan / dinonaktifkan.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('UPDATE produk SET aktif = NOT aktif WHERE id = ? RETURNING nama, aktif');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $aktif = ($row['aktif'] === true || $row['aktif'] === 't' || $row['aktif'] === '1' || $row['aktif'] === 1);
            $_SESSION['flash_produk_ok'] = 'Produk "' . $row['nama'] . '" ' . ($aktif ? 'diaktifkan kembali.' : 'dinonaktifkan dan tidak tampil di toko.');
        }
    }
}

header('Location: dashboard.php');
exit;