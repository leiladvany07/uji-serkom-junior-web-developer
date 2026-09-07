<?php
require_once __DIR__ . '/auth.php';
require_login();

// Tandai status pesan (baru <-> sudah dibalas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_id'])) {
    $id = (int) $_POST['toggle_status_id'];
    $stmt = $pdo->prepare('SELECT status FROM pesan WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    $new = ($current === 'sudah dibalas') ? 'baru' : 'sudah dibalas';
    $pdo->prepare('UPDATE pesan SET status = ? WHERE id = ?')->execute([$new, $id]);
    header('Location: pesan.php');
    exit;
}

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$total = (int) $pdo->query('SELECT COUNT(*) FROM pesan')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare('SELECT * FROM pesan ORDER BY dibuat_pada DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$pesanList = $stmt->fetchAll();

$totalBaru = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();

function inisial($nama) {
    $parts = preg_split('/\s+/', trim($nama));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    return mb_strtoupper($first);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Masuk — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
      <p class="logo">Laluna<span>co.</span></p>
      <p class="admin-sidebar-tagline">handmade knitwear</p>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        <span>Produk</span>
      </a>
      <a href="pesan.php" class="admin-nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        <span>Pesan masuk</span>
        <?php if ($totalBaru > 0): ?><span class="admin-nav-badge"><?= $totalBaru ?></span><?php endif; ?>
      </a>
      <a href="../index.php" target="_blank" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <span>Lihat toko</span>
      </a>
      <a href="logout.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Keluar</span>
      </a>
    </nav>
    <p class="admin-sidebar-footer">Handmade with love &hearts;</p>
  </aside>

  <main class="admin-main">
    <div class="page-intro">
      <div>
        <h1 class="page-title">Pesan masuk</h1>
      </div>
      <svg class="page-illustration" viewBox="0 0 200 130" xmlns="http://www.w3.org/2000/svg">
        <g transform="translate(120 55)">
          <circle cx="0" cy="0" r="46" fill="#A8623B"/>
          <path d="M-38 -12 C-28 -30 -8 -40 12 -38" fill="none" stroke="#7C4527" stroke-width="2" opacity="0.5"/>
          <path d="M-40 2 C-32 -22 -8 -36 18 -31" fill="none" stroke="#7C4527" stroke-width="2" opacity="0.5"/>
          <path d="M-38 16 C-34 -12 -6 -30 24 -22" fill="none" stroke="#7C4527" stroke-width="2" opacity="0.5"/>
          <path d="M-28 30 C-32 2 -2 -22 30 -10" fill="none" stroke="#7C4527" stroke-width="2" opacity="0.5"/>
          <path d="M37 22 C52 30 60 26 59 16" fill="none" stroke="#7C4527" stroke-width="2" stroke-linecap="round"/>
        </g>
        <g stroke="#8C6A2E" stroke-width="3" stroke-linecap="round">
          <line x1="88" y1="30" x2="65" y2="5"/>
          <line x1="152" y1="30" x2="175" y2="5"/>
        </g>
        <path d="M60 90 C40 105 45 118 65 115" fill="none" stroke="#33402C" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
        <g fill="#33402C" opacity="0.4">
          <circle cx="30" cy="20" r="2.5"/>
          <circle cx="42" cy="12" r="2.5"/>
        </g>
      </svg>
    </div>

    <div class="admin-toolbar">
      <div class="admin-toolbar-spacer"></div>
      <div class="admin-search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="pesanSearch" placeholder="Cari nama, email, atau pesan...">
      </div>
    </div>

    <div class="table-scroll">
      <table class="admin-table pesan-table" id="pesanTable">
        <thead>
          <tr><th>#</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Pesan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($pesanList as $i => $p): ?>
          <tr>
            <td><?= $offset + $i + 1 ?></td>
            <td>
              <span class="pesan-nama">
                <span class="avatar-circle"><?= h(inisial($p['nama'])) ?></span>
                <?= h($p['nama']) ?>
              </span>
            </td>
            <td><?= h($p['email']) ?></td>
            <td><?= h($p['telepon'] ?: '-') ?></td>
            <td><?= h($p['isi_pesan']) ?></td>
            <td><?= h(date('d M Y, H:i', strtotime($p['dibuat_pada']))) ?></td>
            <td>
              <form method="post" action="pesan.php">
                <input type="hidden" name="toggle_status_id" value="<?= $p['id'] ?>">
                <button type="submit" class="status-badge <?= ($p['status'] ?? 'baru') === 'sudah dibalas' ? 'status-done' : 'status-new' ?>">
                  <span class="status-dot"></span><?= ($p['status'] ?? 'baru') === 'sudah dibalas' ? 'Sudah dibalas' : 'Baru' ?>
                </button>
              </form>
            </td>
            <td class="admin-actions">
              <a class="btn-pill" href="mailto:<?= h($p['email']) ?>?subject=<?= urlencode('Re: Pesan Anda di Lalunaco') ?>&body=<?= urlencode('Halo ' . $p['nama'] . ',' . "\n\n") ?>">Balas Email</a>
              <?php if (!empty($p['telepon'])): ?>
                <a class="btn-pill btn-pill-solid" href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', ltrim($p['telepon'], '0') === $p['telepon'] ? $p['telepon'] : '62' . ltrim($p['telepon'], '0'))) ?>" target="_blank" rel="noopener">Balas WA</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pesanList)): ?>
          <tr><td colspan="8">Belum ada pesan masuk.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan <?= $total ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $total) ?> dari <?= $total ?> pesan</span>
      <div class="pagination-buttons">
        <a href="?page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">&#8249;</a>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?page=<?= $p ?>" class="pagination-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a href="?page=<?= min($totalPages, $page + 1) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">&#8250;</a>
      </div>
    </div>
  </main>
</div>

<script>
document.getElementById('pesanSearch').addEventListener('input', function () {
  var q = this.value.toLowerCase();
  document.querySelectorAll('#pesanTable tbody tr').forEach(function (row) {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>