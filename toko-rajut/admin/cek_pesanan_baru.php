<?php
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: application/json');

$latestId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM transaksi')->fetchColumn();
$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();

echo json_encode([
    'latest_id' => $latestId,
    'total_baru' => $totalBaruPesanan,
]);