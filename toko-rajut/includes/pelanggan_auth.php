<?php
// Helper akun pelanggan (bukan admin). Sengaja dipisah dari admin/auth.php
// supaya sistem login admin tidak tersentuh sama sekali.

if (session_status() === PHP_SESSION_NONE) session_start();

/** Ambil data pelanggan yang sedang login, atau null kalau belum login. */
function pelanggan_saat_ini(PDO $pdo) {
    if (empty($_SESSION['pelanggan_id'])) return null;
    $stmt = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ?');
    $stmt->execute([$_SESSION['pelanggan_id']]);
    $pelanggan = $stmt->fetch();
    // Kalau ternyata akun sudah tidak ada (dihapus dll), bersihkan sesi basi.
    if (!$pelanggan) {
        unset($_SESSION['pelanggan_id'], $_SESSION['pelanggan_nama']);
        return null;
    }
    return $pelanggan;
}

/** Wajibkan pelanggan login sebelum bisa akses halaman (mis. Pesanan Saya). */
function wajib_login_pelanggan() {
    if (empty($_SESSION['pelanggan_id'])) {
        $tujuan = basename($_SERVER['PHP_SELF']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        header('Location: login.php?redirect=' . urlencode($tujuan));
        exit;
    }
}