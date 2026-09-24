<?php
require_once __DIR__ . '/auth.php';
require_login();

$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-29 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');
$dariFull = $dari . ' 00:00:00';
$sampaiFull = $sampai . ' 23:59:59';

$kategoriId = (int) ($_GET['kategori_id'] ?? 0);

if ($kategoriId > 0) {
    $stmt = $pdo->prepare("SELECT DISTINCT t.kode, t.nama, t.telepon, t.email, t.total, t.status, t.dibuat_pada,
        (SELECT COALESCE(SUM(jumlah),0) FROM transaksi_item WHERE transaksi_id = t.id) AS total_item
        FROM transaksi t
        JOIN transaksi_item ti ON ti.transaksi_id = t.id
        JOIN produk p ON p.id = ti.produk_id
        WHERE t.dibuat_pada BETWEEN ? AND ? AND p.kategori_id = ?
        ORDER BY t.dibuat_pada ASC");
    $stmt->execute([$dariFull, $sampaiFull, $kategoriId]);
} else {
    $stmt = $pdo->prepare("SELECT t.kode, t.nama, t.telepon, t.email, t.total, t.status, t.dibuat_pada,
        (SELECT COALESCE(SUM(jumlah),0) FROM transaksi_item WHERE transaksi_id = t.id) AS total_item
        FROM transaksi t WHERE t.dibuat_pada BETWEEN ? AND ?
        ORDER BY t.dibuat_pada ASC");
    $stmt->execute([$dariFull, $sampaiFull]);
}
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="laporan-penjualan-' . $dari . '_sampai_' . $sampai . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM biar Excel baca UTF-8 dengan benar
fputcsv($out, ['Kode', 'Nama Pelanggan', 'Telepon', 'Email', 'Jumlah Item', 'Total', 'Status', 'Tanggal']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['kode'],
        $r['nama'],
        $r['telepon'],
        $r['email'],
        $r['total_item'],
        $r['total'],
        ucwords($r['status'] ?: 'menunggu konfirmasi'),
        date('Y-m-d H:i', strtotime($r['dibuat_pada'])),
    ]);
}

fclose($out);
exit;