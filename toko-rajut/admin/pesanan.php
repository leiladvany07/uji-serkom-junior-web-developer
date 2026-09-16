<?php
require_once __DIR__ . '/auth.php';
require_login();

// Ambil semua nilai status yang benar-benar dipakai di tabel transaksi
// (bukan tebakan tetap "baru/diproses/selesai"), supaya selalu sesuai data asli.
$statusOptions = $pdo->query('SELECT DISTINCT status FROM transaksi WHERE status IS NOT NULL AND status <> \'\' ORDER BY status')->fetchAll(PDO::FETCH_COLUMN);
if (empty($statusOptions)) $statusOptions = ['menunggu konfirmasi'];

// Ubah status lewat dropdown di tabel (nilai bebas sesuai yang sudah dipakai di sistem)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status_id'])) {
    $id = (int) $_POST['set_status_id'];
    $new = trim($_POST['status_baru'] ?? '');
    if ($new !== '') {
        $pdo->prepare('UPDATE transaksi SET status = ? WHERE id = ?')->execute([$new, $id]);
    }
    header('Location: pesanan.php' . (isset($_GET['page']) ? '?page=' . (int) $_GET['page'] : ''));
    exit;
}

// Warna badge ditentukan dari kata kunci di teks status apapun itu,
// bukan dari daftar nilai tetap — supaya cocok dengan istilah apa pun yang dipakai.
function warna_status_pesanan($status) {
    $s = mb_strtolower((string) $status);
    if ($s === '') return 'status-new';
    if (str_contains($s, 'batal') || str_contains($s, 'tolak')) return 'status-danger';
    if (str_contains($s, 'selesai') || str_contains($s, 'kirim') || str_contains($s, 'sukses')) return 'status-done';
    if (str_contains($s, 'proses') || str_contains($s, 'konfirmasi') || str_contains($s, 'bayar')) return 'status-progress';
    return 'status-new';
}

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$total = (int) $pdo->query('SELECT COUNT(*) FROM transaksi')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT t.*,
    (SELECT COALESCE(SUM(jumlah),0) FROM transaksi_item WHERE transaksi_id = t.id) AS total_item
    FROM transaksi t ORDER BY t.dibuat_pada DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$pesananList = $stmt->fetchAll();

// Badge sidebar: hitung pesanan yang statusnya mengandung kata "baru"/"menunggu"/kosong.
$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();
$totalBaruPesan = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();
$totalOmzetPesanan = (float) $pdo->query('SELECT COALESCE(SUM(total),0) FROM transaksi')->fetchColumn();

function inisial_pesanan($nama) {
    $parts = preg_split('/\s+/', trim((string) $nama));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    return mb_strtoupper($first) ?: '?';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan — Lalunaco</title>
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
      <a href="pesanan.php" class="admin-nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span>Pesanan</span>
        <?php if ($totalBaruPesanan > 0): ?><span class="admin-nav-badge"><?= $totalBaruPesanan ?></span><?php endif; ?>
      </a>
      <a href="pesan.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        <span>Pesan masuk</span>
        <?php if ($totalBaruPesan > 0): ?><span class="admin-nav-badge"><?= $totalBaruPesan ?></span><?php endif; ?>
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
        <h1 class="page-title">Pesanan</h1>
        <p class="page-subtitle">Pesanan yang masuk lewat checkout di toko.</p>
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
      </svg>
    </div>

    <div class="admin-toolbar">
      <div class="admin-toolbar-spacer"></div>
      <div class="admin-search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="pesananSearch" placeholder="Cari kode, nama, atau telepon...">
      </div>
    </div>

    <div class="pesanan-list" id="pesananTable">
      <?php foreach ($pesananList as $i => $t): ?>
        <?php $st = $t['status'] ?: 'menunggu konfirmasi'; ?>
        <article class="pesanan-card <?= warna_status_pesanan($st) ?>-border">
          <div class="pesanan-card-left">
            <span class="avatar-circle avatar-circle-lg"><?= h(inisial_pesanan($t['nama'])) ?></span>
            <div class="pesanan-card-main">
              <div class="pesanan-card-top">
                <strong class="pesanan-kode"><?= h($t['kode']) ?></strong>
                <span class="pesanan-tanggal"><?= h(date('d M Y, H:i', strtotime($t['dibuat_pada']))) ?></span>
              </div>
              <p class="pesanan-pelanggan"><?= h($t['nama']) ?> &middot; <?= h($t['telepon'] ?: '-') ?></p>
              <p class="pesanan-ringkas"><?= (int) $t['total_item'] ?> pcs &middot; <span class="pesanan-total"><?= format_rupiah($t['total']) ?></span></p>
            </div>
          </div>

          <div class="pesanan-card-right">
            <form method="post" action="pesanan.php<?= $page > 1 ? '?page=' . $page : '' ?>" class="status-select-form">
              <input type="hidden" name="set_status_id" value="<?= $t['id'] ?>">
              <select name="status_baru" onchange="this.form.submit()" class="status-badge <?= warna_status_pesanan($st) ?>">
                <?php foreach ($statusOptions as $opt): ?>
                  <option value="<?= h($opt) ?>" <?= $opt === $st ? 'selected' : '' ?>><?= h(ucwords($opt)) ?></option>
                <?php endforeach; ?>
                <?php if (!in_array($st, $statusOptions, true)): ?>
                  <option value="<?= h($st) ?>" selected><?= h(ucwords($st)) ?></option>
                <?php endif; ?>
              </select>
            </form>

            <div class="pesanan-card-actions">
              <a class="icon-btn" href="pesanan_detail.php?id=<?= $t['id'] ?>" title="Lihat detail">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <span>Detail</span>
              </a>
              <?php if (!empty($t['telepon'])): ?>
                <a class="icon-btn icon-btn-wa" href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', ltrim($t['telepon'], '0') === $t['telepon'] ? $t['telepon'] : '62' . ltrim($t['telepon'], '0'))) ?>" target="_blank" rel="noopener" title="Chat WhatsApp">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.3A8.9 8.9 0 0 0 12 4a8.9 8.9 0 0 0-7.8 13.4L3 21l3.7-1.2A8.9 8.9 0 0 0 12 21a8.9 8.9 0 0 0 5.6-15.7zM12 19.3a7.3 7.3 0 0 1-3.9-1.1l-.3-.2-2.6.9.8-2.5-.2-.3A7.3 7.3 0 1 1 19.3 12 7.3 7.3 0 0 1 12 19.3zm4-5.4c-.2-.1-1.3-.6-1.5-.7s-.4-.1-.5.1-.6.7-.7.8-.3.2-.5.1a6 6 0 0 1-1.8-1.1 6.6 6.6 0 0 1-1.2-1.5c-.1-.2 0-.3.1-.4l.3-.4.2-.3a.4.4 0 0 0 0-.4c-.1-.1-.5-1.2-.7-1.6s-.4-.4-.5-.4h-.4a.9.9 0 0 0-.6.3 2.6 2.6 0 0 0-.8 1.9c0 1.1.8 2.2 1 2.4s1.6 2.4 3.9 3.4a13 13 0 0 0 1.3.5 3.2 3.2 0 0 0 1.4.1 2.3 2.3 0 0 0 1.5-1.1 1.9 1.9 0 0 0 .1-1.1c-.1-.1-.2-.2-.5-.3z"/></svg>
                  <span>Chat WA</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (empty($pesananList)): ?>
        <p class="empty-state">Belum ada pesanan masuk.</p>
      <?php endif; ?>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan <?= $total ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $total) ?> dari <?= $total ?> pesanan</span>
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
document.getElementById('pesananSearch').addEventListener('input', function () {
  var q = this.value.toLowerCase();
  document.querySelectorAll('#pesananTable .pesanan-card').forEach(function (card) {
    card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>