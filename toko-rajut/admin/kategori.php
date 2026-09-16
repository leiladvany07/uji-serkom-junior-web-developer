<?php
require_once __DIR__ . '/auth.php';
require_login();

$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();
$totalBaru = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();

$errors = [];
$sukses = '';

// Hapus kategori (hanya kalau tidak ada produk yang masih memakainya)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int) $_POST['hapus_id'];
    $stmtCek = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE kategori_id = ?');
    $stmtCek->execute([$id]);
    $jumlahProduk = (int) $stmtCek->fetchColumn();

    if ($jumlahProduk > 0) {
        $errors[] = "Kategori ini masih dipakai oleh {$jumlahProduk} produk. Pindahkan produknya dulu sebelum menghapus kategori ini.";
    } else {
        $pdo->prepare('DELETE FROM kategori WHERE id = ?')->execute([$id]);
        $sukses = 'Kategori berhasil dihapus.';
    }
}

// Tambah / edit kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kategori'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if ($nama === '') $errors[] = 'Nama kategori wajib diisi.';
    if ($slug === '') $slug = buat_slug($nama);
    else $slug = buat_slug($slug);

    if (empty($errors)) {
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE kategori SET nama = ?, slug = ? WHERE id = ?')->execute([$nama, $slug, $id]);
                $sukses = 'Kategori berhasil diperbarui.';
            } else {
                $pdo->prepare('INSERT INTO kategori (nama, slug) VALUES (?, ?)')->execute([$nama, $slug]);
                $sukses = 'Kategori baru berhasil ditambahkan.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Slug sudah dipakai kategori lain, coba nama/slug yang berbeda.';
        }
    }
}

$kategoriList = $pdo->query("
    SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.kategori_id = k.id) AS jumlah_produk
    FROM kategori k ORDER BY k.nama
")->fetchAll();

$editId = (int) ($_GET['edit'] ?? 0);
$editData = null;
if ($editId > 0) {
    foreach ($kategoriList as $k) {
        if ((int) $k['id'] === $editId) { $editData = $k; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kategori — Lalunaco</title>
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
      <a href="kategori.php" class="admin-nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.82 0l4.6-4.6a2 2 0 0 0 0-2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
        <span>Kategori</span>
      </a>
      <a href="pesanan.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span>Pesanan</span>
        <?php if ($totalBaruPesanan > 0): ?><span class="admin-nav-badge"><?= $totalBaruPesanan ?></span><?php endif; ?>
      </a>
      <a href="laporan.php" class="admin-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        <span>Laporan</span>
      </a>
      <a href="pesan.php" class="admin-nav-item">
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
        <h1 class="page-title">Kategori Produk</h1>
        <p class="page-subtitle">Kelola kategori yang dipakai untuk mengelompokkan produk di toko.</p>
      </div>
    </div>

    <?php if ($sukses): ?><div class="email-notif-banner email-notif-ok"><?= h($sukses) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
      <ul class="form-errors">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="kategori-layout">
      <div class="table-scroll">
        <table class="admin-table">
          <thead><tr><th>Nama</th><th>Slug</th><th>Jumlah Produk</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php foreach ($kategoriList as $k): ?>
            <tr>
              <td><?= h($k['nama']) ?></td>
              <td><code><?= h($k['slug']) ?></code></td>
              <td><?= (int) $k['jumlah_produk'] ?> produk</td>
              <td class="admin-actions">
                <a class="btn-pill" href="kategori.php?edit=<?= $k['id'] ?>">Edit</a>
                <form method="post" action="kategori.php" onsubmit="return confirm('Hapus kategori ini?');">
                  <input type="hidden" name="hapus_id" value="<?= $k['id'] ?>">
                  <button type="submit" class="link-danger">Hapus</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($kategoriList)): ?>
              <tr><td colspan="4">Belum ada kategori.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pesanan-side-card">
        <h2 class="pesanan-detail-subheading"><?= $editData ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
        <form method="post" action="kategori.php" class="admin-form" style="padding:0;border:none;">
          <input type="hidden" name="id" value="<?= $editData ? $editData['id'] : '' ?>">
          <label>Nama Kategori
            <input type="text" name="nama" value="<?= h($editData['nama'] ?? '') ?>" required placeholder="mis. Sweater Rajut">
          </label>
          <label>Slug (opsional, otomatis dari nama)
            <input type="text" name="slug" value="<?= h($editData['slug'] ?? '') ?>" placeholder="mis. sweater-rajut">
          </label>
          <button type="submit" name="simpan_kategori" value="1" class="btn btn-primary"><?= $editData ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
          <?php if ($editData): ?><a href="kategori.php" class="admin-back" style="text-align:center;">Batal edit</a><?php endif; ?>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>