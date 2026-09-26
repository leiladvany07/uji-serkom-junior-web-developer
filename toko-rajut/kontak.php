<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/env.php';

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/
$berhasil = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $isiPesan = trim($_POST['isi_pesan'] ?? '');

    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if ($isiPesan === '') $errors[] = 'Pesan tidak boleh kosong.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO pesan (nama, email, telepon, isi_pesan) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $email, $telepon, $isiPesan]);
        $berhasil = true;
    }
}

$page_title = 'Kontak';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <h1 class="section-title">Hubungi kami</h1>
    <p class="contact-lead">
        Ada pertanyaan seputar produk, pemesanan custom, atau kerja sama? 
        Kirim pesan melalui form di bawah ini.
    </p>

    <div class="contact-layout">
        <form class="contact-form" method="post" action="kontak.php" novalidate>
            <?php if ($berhasil): ?>
                <p class="form-success">
                    Pesan Anda sudah terkirim. Kami akan membalas secepatnya.
                </p>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <ul class="form-errors">
                    <?php foreach ($errors as $e): ?>
                        <li><?= h($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <label>
                Nama
                <input type="text" name="nama" value="<?= h($_POST['nama'] ?? '') ?>" required>
            </label>

            <label>
                Email
                <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
            </label>

            <label>
                Telepon (opsional)
                <input type="text" name="telepon" value="<?= h($_POST['telepon'] ?? '') ?>">
            </label>

            <label>
                Pesan
                <textarea name="isi_pesan" rows="5" required><?= h($_POST['isi_pesan'] ?? '') ?></textarea>
            </label>

            <button type="submit" class="btn btn-primary">Kirim pesan</button>
        </form>

        <div class="contact-info">
            <?php 
            $infos = [
                ['Alamat', 'Jl. Nusa Penida, No. 27, Madiun, Jawa Timur'],
                ['Email', 'leiladvany02@gmail.com'],
                ['WhatsApp', '+62 889-8950-5932']
            ];
            foreach ($infos as [$label, $val]): 
            ?>
                <div class="contact-item">
                    <span class="contact-label"><?= $label ?></span>
                    <span class="contact-value"><?= $val ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>