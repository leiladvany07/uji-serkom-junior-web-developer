<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['pelanggan_id'])) {
    header('Location: pesanan_saya.php');
    exit;
}

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');
$redirectAman = (is_string($redirect) && preg_match('/^[a-zA-Z0-9_\-\.\?=&]+$/', $redirect)) ? $redirect : '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM pelanggan WHERE email = ?');
    $stmt->execute([$email]);
    $pelanggan = $stmt->fetch();

    if ($pelanggan && password_verify($password, $pelanggan['password'])) {
        $_SESSION['pelanggan_id'] = $pelanggan['id'];
        $_SESSION['pelanggan_nama'] = $pelanggan['nama'];
        header('Location: ' . ($redirectAman !== '' ? $redirectAman : 'pesanan_saya.php'));
        exit;
    }
    $error = 'Email atau password salah.';
}

$page_title = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<section class="section auth-page">
  <h1 class="section-title">Masuk ke Akun</h1>

  <?php if ($error): ?>
    <ul class="form-errors"><li><?= h($error) ?></li></ul>
  <?php endif; ?>

  <div class="contact-layout">
    <form class="contact-form" method="post" action="login.php<?= $redirectAman !== '' ? '?redirect=' . urlencode($redirectAman) : '' ?>" novalidate>
      <input type="hidden" name="redirect" value="<?= h($redirectAman) ?>">
      <label>Email
        <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button type="submit" class="btn btn-primary">Masuk</button>
      <p class="checkout-note">Belum punya akun? <a href="daftar.php<?= $redirectAman !== '' ? '?redirect=' . urlencode($redirectAman) : '' ?>" style="color:var(--clay);font-weight:700;">Daftar di sini</a>. Atau lanjut <a href="checkout.php" style="color:var(--clay);font-weight:700;">checkout sebagai tamu</a>.</p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>