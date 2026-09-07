<?php
require_once __DIR__ . '/auth.php';
require_login();

$kategoriList = $pdo->query('SELECT * FROM kategori ORDER BY nama')->fetchAll();

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
    <p class="logo">Laluna<span>co.</span></p>
    <nav class="admin-nav">
      <a href="dashboard.php" class="active">Produk</a>
      <a href="pesan.php">Pesan masuk</a>
      <a href="../index.php" target="_blank">Lihat toko</a>
      <a href="logout.php">Keluar</a>
    </nav>
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