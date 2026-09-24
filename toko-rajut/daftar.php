<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['pelanggan_id'])) {
    header('Location: pesanan_saya.php');
    exit;
}

$errors = [];
$nama = $email = $no_hp = $alamat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if ($no_hp === '') $errors[] = 'Nomor HP wajib diisi.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $konfirmasi) $errors[] = 'Konfirmasi password tidak sama.';

    if (empty($errors)) {
        $cek = $pdo->prepare('SELECT id FROM pelanggan WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $errors[] = 'Email ini sudah terdaftar. Silakan masuk.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO pelanggan (nama, email, password, no_hp, alamat) VALUES (?,?,?,?,?) RETURNING id');
        $stmt->execute([$nama, $email, password_hash($password, PASSWORD_DEFAULT), $no_hp, $alamat ?: null]);
        $id = $stmt->fetchColumn();

        $_SESSION['pelanggan_id'] = $id;
        $_SESSION['pelanggan_nama'] = $nama;

        $redirect = $_GET['redirect'] ?? '';
        if (is_string($redirect) && preg_match('/^[a-zA-Z0-9_\-\.\?=&]+$/', $redirect)) {
            header('Location: ' . $redirect);
        } else {
            header('Location: pesanan_saya.php');
        }
        exit;
    }
}

$page_title = 'Daftar Akun';
require __DIR__ . '/includes/header.php';
?>

<section class="section auth-page">
  <h1 class="section-title">Daftar Akun</h1>

  <?php if (!empty($errors)): ?>
    <ul class="form-errors">
      <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="contact-layout">
    <form class="contact-form" method="post" action="daftar.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" novalidate>
      <label>Nama Lengkap
        <input type="text" name="nama" value="<?= h($nama) ?>" required>
      </label>
      <label>Email
        <input type="email" name="email" value="<?= h($email) ?>" required>
      </label>
      <label>Nomor HP / WhatsApp
        <input type="text" name="no_hp" value="<?= h($no_hp) ?>" required>
      </label>
      <label>Alamat (opsional, bisa diisi nanti saat checkout)
        <textarea name="alamat" rows="3"><?= h($alamat) ?></textarea>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <label>Konfirmasi Password
        <input type="password" name="konfirmasi_password" required>
      </label>
      <button type="submit" class="btn btn-primary">Daftar</button>
      <p class="checkout-note">Sudah punya akun? <a href="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" style="color:var(--clay);font-weight:700;">Masuk di sini</a>.</p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>