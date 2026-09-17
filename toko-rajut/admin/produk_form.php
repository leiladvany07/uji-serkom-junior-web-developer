<?php
require_once __DIR__ . '/auth.php';
require_login();

$kategoriList = $pdo->query('SELECT * FROM kategori ORDER BY nama')->fetchAll();

$totalBaruPesan = (int) $pdo->query("SELECT COUNT(*) FROM pesan WHERE status = 'baru' OR status IS NULL")->fetchColumn();
$totalBaruPesanan = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status IS NULL OR status = '' OR status ILIKE '%baru%' OR status ILIKE '%menunggu%'")->fetchColumn();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$produk = [
    'nama' => '', 'kategori_id' => $kategoriList[0]['id'] ?? '', 'deskripsi' => '',
    'harga' => '', 'stok' => '', 'warna' => '', 'gambar' => '',
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $produk = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk['nama'] = trim($_POST['nama'] ?? '');
    $produk['kategori_id'] = (int) ($_POST['kategori_id'] ?? 0);
    $produk['deskripsi'] = trim($_POST['deskripsi'] ?? '');
    $produk['harga'] = (int) ($_POST['harga'] ?? 0);
    $produk['stok'] = (int) ($_POST['stok'] ?? 0);
    $produk['warna'] = trim($_POST['warna'] ?? '');

    if ($produk['nama'] === '') $errors[] = 'Nama produk wajib diisi.';
    if (!$produk['kategori_id']) $errors[] = 'Pilih kategori.';
    if ($produk['harga'] <= 0) $errors[] = 'Harga harus lebih dari 0.';

    // Upload foto baru (kalau ada dipilih)
    $assetsDir = __DIR__ . '/../assets/';
    $uploadedNames = [];
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

    if (!empty($_FILES['gambar_files']['name'][0])) {
        foreach ($_FILES['gambar_files']['name'] as $i => $originalName) {
            if ($_FILES['gambar_files']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors[] = "File \"$originalName\" bukan format gambar yang didukung.";
                continue;
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
            $newName = strtolower($safeBase) . '-' . substr(uniqid(), -6) . '.' . $ext;

            if (move_uploaded_file($_FILES['gambar_files']['tmp_name'][$i], $assetsDir . $newName)) {
                $uploadedNames[] = $newName;
            } else {
                $errors[] = "Gagal mengunggah file \"$originalName\".";
            }
        }
    }

       // Foto lama yang tersisa (setelah dikurangi yang dicentang "hapus")
    $existingList = isset($_POST['keep_existing']) ? array_filter(array_map('trim', explode(',', $_POST['keep_existing']))) : [];
    $hapusFoto = $_POST['hapus_foto'] ?? [];
    $existingList = array_values(array_diff($existingList, $hapusFoto));

    $finalList = array_merge($existingList, $uploadedNames);
    $produk['gambar'] = !empty($finalList) ? implode(', ', $finalList) : '';

    if (empty($errors)) {
    $slug = buat_slug($produk['nama']);
    if ($id) {
            $stmt = $pdo->prepare('UPDATE produk SET nama=?, kategori_id=?, deskripsi=?, harga=?, stok=?, warna=?, gambar=?, slug=? WHERE id=?');
            $stmt->execute([$produk['nama'], $produk['kategori_id'], $produk['deskripsi'], $produk['harga'], $produk['stok'], $produk['warna'], $produk['gambar'], $slug, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO produk (nama, kategori_id, deskripsi, harga, stok, warna, gambar, slug) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$produk['nama'], $produk['kategori_id'], $produk['deskripsi'], $produk['harga'], $produk['stok'], $produk['warna'], $produk['gambar'], $slug]);
        }
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $id ? 'Ubah' : 'Tambah' ?> Produk — Lalunaco</title>
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
      <a href="dashboard.php" class="admin-nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        <span>Produk</span>
      </a>
      <a href="kategori.php" class="admin-nav-item">
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
    <a href="dashboard.php" class="admin-back">&larr; Kembali ke daftar produk</a>
    <div class="admin-header">
      <h1><?= $id ? 'Ubah produk' : 'Tambah produk' ?></h1>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="form-errors"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form class="admin-form" method="post" action="produk_form.php<?= $id ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="keep_existing" value="<?= h($produk['gambar']) ?>">
      <label>Nama produk
        <input type="text" name="nama" value="<?= h($produk['nama']) ?>" required>
      </label>
      <label>Kategori
        <select name="kategori_id" required>
          <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $k['id'] == $produk['kategori_id'] ? 'selected' : '' ?>><?= h($k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Deskripsi
        <textarea name="deskripsi" rows="4"><?= h($produk['deskripsi']) ?></textarea>
      </label>
      <div class="form-row">
        <label>Harga (Rp)
          <input type="number" name="harga" value="<?= h($produk['harga']) ?>" required>
        </label>
        <label>Stok
          <input type="number" name="stok" value="<?= h($produk['stok']) ?>" required>
        </label>
      </div>
      <label>Warna
        <input type="text" name="warna" value="<?= h($produk['warna']) ?>">
        <span class="foto-preview-hint">Pisahkan dengan koma kalau ada lebih dari 1 pilihan warna, contoh: Krem, Putih Gading</span>
      </label>
            <label>Foto produk
        <?php $gambarPreview = array_filter(array_map('trim', explode(',', $produk['gambar']))); ?>
        <?php if (!empty($gambarPreview)): ?>
          <div class="foto-preview-row">
            <?php foreach ($gambarPreview as $g): ?>
              <div class="foto-preview-item">
                <img src="../assets/<?= h($g) ?>" alt="" class="foto-preview-thumb">
                <label class="foto-preview-hapus">
                  <input type="checkbox" name="hapus_foto[]" value="<?= h($g) ?>"> Hapus
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <span class="foto-preview-hint">Centang "Hapus" pada foto yang ingin dihilangkan.</span>
        <?php endif; ?>
        <input type="file" name="gambar_files[]" accept="image/*" multiple>
        <span class="foto-preview-hint">Bisa tambah foto baru sekaligus (untuk slider).</span>
      </label>
      <button type="submit" class="btn btn-primary"><?= $id ? 'Simpan perubahan' : 'Tambah produk' ?></button>
    </form>
  </main>
</div>
</body>
</html>