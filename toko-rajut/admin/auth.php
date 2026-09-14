<?php
session_start();
require_once __DIR__ . '/../config.php';

function require_login() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
