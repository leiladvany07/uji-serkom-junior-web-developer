<?php
require_once __DIR__ . '/auth.php';
require_login();

// Ubah status transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_id'])) {
    $id = (int) $_POST['update_status_id'];
    $status = trim($_POST['status'] ?? '');
    $allowed = ['menunggu konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
    if (in_array($status, $allowed)) {
        $pdo->prepare('UPDATE transaksi SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
    header('Location: transaksi.php');
    exit;
}

$transaksiList = $pdo->query('SELECT * FROM transaksi ORDER BY dibuat_pada DESC')->fetchAll();
$totalPenjualan = $pdo->query("SELECT COALESCE(SUM(total),0) FROM transaksi WHERE status != 'dibatalkan'")->fetchColumn();
$totalTransaksi = count($transaksiList);
$menungguKonfirmasi = count(array_filter($transaksiList, fn($t) => $t['status'] === 'menunggu konfirmasi'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaksi — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-header">
      <h1>Transaksi</h1>
    </div>

    <div class="admin-stats">
      <div class="stat-card"><span class="stat-value"><?= format_rupiah($totalPenjualan) ?></span><span class="stat-label">Total penjualan</span></div>
      <div class="stat-card"><span class="stat-value"><?= $totalTransaksi ?></span><span class="stat-label">Total transaksi</span></div>
      <div class="stat-card"><span class="stat-value"><?= $menungguKonfirmasi ?></span><span class="stat-label">Menunggu konfirmasi</span></div>
    </div>

    <div class="table-scroll">
      <table class="admin-table">
        <thead>
          <tr><th>Kode</th><th>Nama</th><th>Telepon</th><th>Total</th><th>Tanggal</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($transaksiList as $t): ?>
          <tr>
            <td><?= h($t['kode']) ?></td>
            <td><?= h($t['nama']) ?></td>
            <td><?= h($t['telepon']) ?></td>
            <td><?= format_rupiah($t['total']) ?></td>
            <td><?= h(date('d M Y, H:i', strtotime($t['dibuat_pada']))) ?></td>
            <td>
              <form method="post" action="transaksi.php" style="display:flex;gap:0.4rem;align-items:center;">
                <input type="hidden" name="update_status_id" value="<?= $t['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach (['menunggu konfirmasi','diproses','dikirim','selesai','dibatalkan'] as $s): ?>
                    <option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($transaksiList)): ?>
          <tr><td colspan="6">Belum ada transaksi.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>