<?php

define('DB_HOST', getenv('PGHOST') ?: 'localhost');
define('DB_PORT', getenv('PGPORT') ?: '5432');
define('DB_NAME', getenv('PGDATABASE') ?: 'toko_rajutt');
define('DB_USER', getenv('PGUSER') ?: 'postgres');
define('DB_PASS', getenv('PGPASSWORD') ?: 'postgres');
define('BASE_URL', '');

try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    // Paksa koneksi database selalu pakai waktu Indonesia (WIB), apa pun
    // timezone default server hosting-nya (Railway dkk biasanya UTC).
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan PostgreSQL berjalan, database "toko_rajutt" sudah dibuat di pgAdmin, dan db/rajut_postgres.sql sudah dijalankan di dalamnya. Detail: ' . $e->getMessage());
}

function format_rupiah($angka) {
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

function h($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

function first_image($gambar) {
    $parts = array_filter(array_map('trim', explode(',', (string) $gambar)));
    return $parts ? reset($parts) : 'produk-1.svg';
}

function parse_warna($warna) {
    return array_values(array_filter(array_map('trim', explode(',', (string) $warna)), fn($w) => $w !== ''));
}

// Cek apakah suatu teks status berarti "dibatalkan" (cocok dengan kata
// kunci apa pun, bukan nilai tetap, supaya fleksibel sesuai istilah yang dipakai).
function is_status_batal($status) {
    return str_contains(mb_strtolower((string) $status), 'batal');
}

// Sinkronkan stok produk saat status pesanan berubah:
// - status baru "dibatalkan" & belum pernah dikembalikan -> stok dikembalikan (+jumlah)
// - status baru BUKAN "dibatalkan" tapi sebelumnya sudah dikembalikan -> stok dipotong lagi (-jumlah)
// Dipanggil setiap kali status pesanan disimpan, dari admin/pesanan.php dan admin/pesanan_detail.php.
function sinkron_stok_pesanan(PDO $pdo, int $transaksiId, string $statusBaru) {
    $stmt = $pdo->prepare('SELECT stok_dikembalikan FROM transaksi WHERE id = ?');
    $stmt->execute([$transaksiId]);
    $sudahDikembalikan = (bool) $stmt->fetchColumn();
    $batalSekarang = is_status_batal($statusBaru);

    if ($batalSekarang && !$sudahDikembalikan) {
        $items = $pdo->prepare('SELECT produk_id, warna, jumlah FROM transaksi_item WHERE transaksi_id = ?');
        $items->execute([$transaksiId]);
        foreach ($items->fetchAll() as $it) {
            if (!empty($it['produk_id'])) ubah_stok_item($pdo, (int) $it['produk_id'], (string) ($it['warna'] ?? ''), (int) $it['jumlah']);
        }
        $pdo->prepare('UPDATE transaksi SET stok_dikembalikan = TRUE WHERE id = ?')->execute([$transaksiId]);
    } elseif (!$batalSekarang && $sudahDikembalikan) {
        $items = $pdo->prepare('SELECT produk_id, warna, jumlah FROM transaksi_item WHERE transaksi_id = ?');
        $items->execute([$transaksiId]);
        foreach ($items->fetchAll() as $it) {
            if (!empty($it['produk_id'])) ubah_stok_item($pdo, (int) $it['produk_id'], (string) ($it['warna'] ?? ''), -(int) $it['jumlah']);
        }
        $pdo->prepare('UPDATE transaksi SET stok_dikembalikan = FALSE WHERE id = ?')->execute([$transaksiId]);
    }
}

// ===== STOK & STATUS PRODUK =====
class StokTidakCukupException extends Exception {}

// ----- Stok per warna -----
// Produk dengan 2+ warna menyimpan stok tiap warna di tabel produk_warna.
// Kolom produk.stok berisi TOTAL semua warna (dijaga sinkron oleh aplikasi).
function varian_tersedia(PDO $pdo): bool {
    static $ada = null;
    if ($ada === null) {
        $ada = $pdo->query("SELECT to_regclass('produk_warna')")->fetchColumn() !== null;
    }
    return $ada;
}

// Hasil: [produk_id => [warna => stok]]. Produk tanpa stok per warna tidak ikut.
function ambil_stok_varian(PDO $pdo, array $produkIds, bool $kunci = false): array {
    $produkIds = array_values(array_unique(array_map('intval', $produkIds)));
    if (!$produkIds || !varian_tersedia($pdo)) return [];
    $ph = implode(',', array_fill(0, count($produkIds), '?'));
    $stmt = $pdo->prepare("SELECT produk_id, warna, stok FROM produk_warna WHERE produk_id IN ($ph) ORDER BY id" . ($kunci ? ' FOR UPDATE' : ''));
    $stmt->execute($produkIds);
    $map = [];
    foreach ($stmt->fetchAll() as $r) { $map[(int) $r['produk_id']][(string) $r['warna']] = (int) $r['stok']; }
    return $map;
}

// Stok yang berlaku untuk produk + warna tertentu.
function stok_untuk(array $p, string $warna, array $varian): int {
    $pid = (int) $p['id'];
    if (!empty($varian[$pid])) return (int) ($varian[$pid][$warna] ?? 0);
    return (int) $p['stok'];
}

// Samakan produk.stok dengan jumlah stok semua warnanya.
function sinkron_total_stok(PDO $pdo, int $produkId): void {
    $pdo->prepare('UPDATE produk SET stok = (SELECT COALESCE(SUM(stok), 0) FROM produk_warna WHERE produk_id = ?) WHERE id = ?')
        ->execute([$produkId, $produkId]);
}

// Tambah / kurangi stok satu item pesanan (dipakai saat pesanan dibatalkan / diaktifkan lagi).
function ubah_stok_item(PDO $pdo, int $produkId, string $warna, int $delta): void {
    if ($warna !== '' && varian_tersedia($pdo)) {
        $u = $pdo->prepare('UPDATE produk_warna SET stok = GREATEST(stok + ?, 0) WHERE produk_id = ? AND warna = ?');
        $u->execute([$delta, $produkId, $warna]);
        if ($u->rowCount() > 0) {
            sinkron_total_stok($pdo, $produkId);
            return;
        }
    }
    $pdo->prepare('UPDATE produk SET stok = GREATEST(stok + ?, 0) WHERE id = ?')->execute([$delta, $produkId]);
}

// Produk dianggap aktif kalau kolom "aktif" bernilai true (atau kolomnya belum ada).
function produk_aktif(array $p): bool {
    if (!array_key_exists('aktif', $p)) return true;
    return $p['aktif'] === true || $p['aktif'] === 't' || $p['aktif'] === '1' || $p['aktif'] === 1;
}

// Pesan masalah untuk satu produk yang dipesan sebanyak $qty, atau null kalau aman.
function pesan_masalah_stok(array $p, int $qty): ?string {
    if (!produk_aktif($p)) return '"' . $p['nama'] . '" sudah tidak tersedia.';
    $stok = (int) $p['stok'];
    if ($stok <= 0) return 'Stok "' . $p['nama'] . '" sedang habis.';
    if ($qty > $stok) return 'Stok "' . $p['nama'] . '" hanya tersisa ' . $stok . ' pcs (kamu memesan ' . $qty . ' pcs).';
    return null;
}

// Cek semua item ['produk' => row, 'jumlah' => n, 'warna' => teks]. Jumlah dijumlahkan
// per produk; untuk produk dengan stok per warna, per produk + warna.
function cek_stok_items(array $items, array $varian = []): array {
    $perKunci = [];
    foreach ($items as $it) {
        $p = $it['produk'];
        $pid = (int) $p['id'];
        $pakaiVarian = !empty($varian[$pid]);
        $warna = $pakaiVarian ? (string) ($it['warna'] ?? '') : '';
        $kunci = $pid . '::' . $warna;
        if (!isset($perKunci[$kunci])) {
            $row = $p;
            if ($pakaiVarian) {
                $row['stok'] = (int) ($varian[$pid][$warna] ?? 0);
                $row['nama'] = $p['nama'] . ' (' . $warna . ')';
            }
            $perKunci[$kunci] = ['produk' => $row, 'jumlah' => 0];
        }
        $perKunci[$kunci]['jumlah'] += (int) $it['jumlah'];
    }
    $pesan = [];
    foreach ($perKunci as $d) {
        $m = pesan_masalah_stok($d['produk'], $d['jumlah']);
        if ($m !== null) $pesan[] = $m;
    }
    return $pesan;
}

function id_dari_key_keranjang($key): int {
    return (int) explode('::', (string) $key, 2)[0];
}

function warna_dari_key_keranjang($key): string {
    return explode('::', (string) $key, 2)[1] ?? '';
}

// Samakan isi keranjang (session) dengan kondisi produk terbaru: produk nonaktif / habis
// dibuang, jumlah melebihi stok dikurangi. Mengembalikan daftar pesan perubahan.
function sinkron_keranjang(PDO $pdo): array {
    $pesan = [];
    $keranjang = $_SESSION['keranjang'] ?? [];
    if (empty($keranjang)) return $pesan;

    $ids = array_values(array_unique(array_map('id_dari_key_keranjang', array_keys($keranjang))));
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nama, stok, aktif FROM produk WHERE id IN ($ph)");
    $stmt->execute($ids);
    $produk = [];
    foreach ($stmt->fetchAll() as $p) { $produk[(int) $p['id']] = $p; }
    $varian = ambil_stok_varian($pdo, $ids);

    $terpakai = [];
    foreach ($keranjang as $key => $jumlah) {
        $id = id_dari_key_keranjang($key);
        $warna = warna_dari_key_keranjang($key);
        $p = $produk[$id] ?? null;
        if (!$p || !produk_aktif($p)) {
            unset($_SESSION['keranjang'][$key]);
            $pesan[] = ($p ? '"' . $p['nama'] . '"' : 'Salah satu produk') . ' sudah tidak tersedia dan dihapus dari keranjang.';
            continue;
        }
        $pakaiVarian = !empty($varian[$id]);
        $slot = $pakaiVarian ? $id . '::' . $warna : (string) $id;
        $label = $p['nama'] . ($pakaiVarian ? ' (' . $warna . ')' : '');
        $sisa = stok_untuk($p, $warna, $varian) - ($terpakai[$slot] ?? 0);
        if ($sisa <= 0) {
            unset($_SESSION['keranjang'][$key]);
            $pesan[] = 'Stok "' . $label . '" habis, produk dihapus dari keranjang.';
            continue;
        }
        if ($jumlah > $sisa) {
            $_SESSION['keranjang'][$key] = $sisa;
            $jumlah = $sisa;
            $pesan[] = 'Jumlah "' . $label . '" disesuaikan menjadi ' . $sisa . ' pcs sesuai stok tersisa.';
        }
        $terpakai[$slot] = ($terpakai[$slot] ?? 0) + $jumlah;
    }
    return $pesan;
}

function kategori_icon($nama) {
    $n = mb_strtolower((string) $nama);
    if (str_contains($n, 'tas') || str_contains($n, 'dompet')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="9" width="18" height="12" rx="2"/><path d="M8 9V6a4 4 0 0 1 8 0v3"/></svg>';
    }
    if (str_contains($n, 'mainan') || str_contains($n, 'boneka') || str_contains($n, 'amigurumi')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="7"/><circle cx="6" cy="6" r="2.4"/><circle cx="18" cy="6" r="2.4"/><circle cx="10" cy="12" r="0.8" fill="currentColor" stroke="none"/><circle cx="14" cy="12" r="0.8" fill="currentColor" stroke="none"/><path d="M9.5 15.5c1 1 4 1 5 0"/></svg>';
    }
    if (str_contains($n, 'topi')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13a8 8 0 0 1 16 0"/><rect x="3" y="13" width="18" height="3" rx="1.5"/><circle cx="12" cy="6" r="1.2" fill="currentColor" stroke="none"/></svg>';
    }
    if (str_contains($n, 'syal') || str_contains($n, 'scarf')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5c3 3 15 3 18 0"/><path d="M16 5c1 5-1 8 2 13"/><path d="M18 18l-2 2 2 2"/></svg>';
    }
    if (str_contains($n, 'sweater') || str_contains($n, 'cardigan') || str_contains($n, 'knit')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4l4 2 4-2 3 4-3 2v10H5V10L2 8z"/><rect x="9.5" y="2.5" width="5" height="3" rx="1"/><path d="M6 15h12M6 12h12"/></svg>';
    }
    if (str_contains($n, 'baju') || str_contains($n, 'atasan') || str_contains($n, 'blus') || str_contains($n, 'vest')) {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4l3 2 3-1 3 1 3-2 3 4-3 2v11H6V10L3 8z"/></svg>';
    }
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 4c2.5 2.5 2.5 13.5 0 16M12 4c-2.5 2.5-2.5 13.5 0 16M4 12h16"/></svg>';
}

function buat_slug($teks) {
    $teks = str_replace(
        ['á','à','â','ä','ã','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','ö','õ','ú','ù','û','ü','ñ','ç',
         'Á','À','Â','Ä','Ã','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Ö','Õ','Ú','Ù','Û','Ü','Ñ','Ç'],
        ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n','c',
         'a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n','c'],
        $teks
    );
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $teks), '-'));
    return $slug !== '' ? $slug : 'produk';
}