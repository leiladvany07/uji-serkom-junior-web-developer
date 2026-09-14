<?php
require_once __DIR__ . '/auth.php';
require_login();

$kategoriList = $pdo->query('SELECT * FROM kategori ORDER BY nama')->fetchAll();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$produk = [
    'nama' => '', 'kategori_id' => $kategoriList[0]['id'] ?? '', 'deskripsi' => '',
    'harga' => '', 'stok' => '', 'warna' => '', 'gambar' => 'produk-1.svg',
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
    $produk['gambar'] = trim($_POST['gambar'] ?? 'produk-1.svg');

    if ($produk['nama'] === '') $errors[] = 'Nama produk wajib diisi.';
    if (!$produk['kategori_id']) $errors[] = 'Pilih kategori.';
    if ($produk['harga'] <= 0) $errors[] = 'Harga harus lebih dari 0.';

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $produk['nama']), '-'));
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
    <p class="logo">Laluna<span>co.</span></p>
    <nav class="admin-nav">
      <a href="dashboard.php" class="active">Produk</a>
      <a href="pesan.php">Pesan masuk</a>
      <a href="../index.php" target="_blank">Lihat toko</a>
      <a href="logout.php">Keluar</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="admin-header">
      <h1><?= $id ? 'Ubah produk' : 'Tambah produk' ?></h1>
      <a href="dashboard.php" class="admin-back">&larr; Kembali</a>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="form-errors"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form class="admin-form" method="post" action="produk_form.php<?= $id ? '?id=' . $id : '' ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
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
      </label>
      <label>Nama file gambar (di folder assets/)
        <input type="text" name="gambar" value="<?= h($produk['gambar']) ?>">
      </label>
      <button type="submit" class="btn btn-primary"><?= $id ? 'Simpan perubahan' : 'Tambah produk' ?></button>
    </form>
  </main>
</div>
</body>
</html>
