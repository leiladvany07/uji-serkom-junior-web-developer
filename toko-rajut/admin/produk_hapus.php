<?php
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM produk WHERE id = ?');
        $stmt->execute([$id]);
    }
}

header('Location: dashboard.php');
exit;
