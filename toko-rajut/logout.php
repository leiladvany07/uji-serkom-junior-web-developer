<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['pelanggan_id'], $_SESSION['pelanggan_nama']);
header('Location: index.php');
exit;