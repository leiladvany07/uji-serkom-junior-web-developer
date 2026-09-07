<?php
require_once __DIR__ . '/config.php';

echo '<pre>';
echo "Database aktif: " . $pdo->query('SELECT current_database()')->fetchColumn() . "\n";
echo "Schema aktif: " . $pdo->query('SELECT current_schema()')->fetchColumn() . "\n";
echo "Host:Port (dari config.php): " . DB_HOST . ':' . DB_PORT . "\n\n";

echo "Kolom pada tabel produk yang terlihat oleh PHP:\n";
$cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'produk'")->fetchAll();
foreach ($cols as $c) {
    echo "- " . $c['column_name'] . "\n";
}
echo '</pre>';