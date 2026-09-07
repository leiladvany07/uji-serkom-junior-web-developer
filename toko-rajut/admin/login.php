<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk Admin — Lalunaco</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-auth-wrap">
  <form class="admin-auth-card" method="post" action="login.php">
    <p class="logo">Laluna<span>co.</span></p>
    <h1 class="admin-auth-title">Masuk ke panel admin</h1>
    <?php if ($error): ?><p class="form-errors"><?= h($error) ?></p><?php endif; ?>
    <label>Username
      <input type="text" name="username" required autofocus>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button type="submit" class="btn btn-primary">Masuk</button>
    <a href="../index.php" class="admin-back">&larr; Kembali ke toko</a>
  </form>
</div>
</body>
</html>
