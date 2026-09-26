<?php
/**
 * Helper notifikasi status pesanan ke pembeli.
 *
 * WhatsApp: membuat link wa.me dengan pesan otomatis.
 * (Fitur email dinonaktifkan — Railway tidak mengizinkan koneksi
 * SMTP keluar dan sering menyebabkan error di production.)
 */

/** Label per status, dipakai di WhatsApp maupun badge email. */
function label_status_emoji(string $status): string {
    $labels = [
        'menunggu konfirmasi' => 'Menunggu Konfirmasi',
        'diproses'            => 'Diproses',
        'dikirim'             => 'Dikirim',
        'selesai'             => 'Selesai',
        'dibatalkan'          => 'Dibatalkan',
    ];
    $key = strtolower(trim($status));
    return $labels[$key] ?? ucwords($status);
}

/** Template pesan berdasarkan status pesanan — ringkas, satu pesan saja. */
function template_pesan_status(): array {
    return [
        'menunggu konfirmasi' =>
            "halo {nama}, pesanan anda dengan kode {kode} sudah kami terima dan sedang menunggu konfirmasi. terima kasih sudah berbelanja di lalunaco.",

        'diproses' =>
            "halo {nama}, kabar baik! pesanan anda dengan kode {kode} sedang kami proses. terima kasih sudah berbelanja di lalunaco.",

        'dikirim' =>
            "halo {nama}, pesanan anda dengan kode {kode} sudah dikirim. terima kasih sudah berbelanja di lalunaco.",

        'selesai' =>
            "halo {nama}, pesanan anda dengan kode {kode} sudah selesai. terima kasih sudah berbelanja di lalunaco, semoga produknya berkenan.",

        'dibatalkan' =>
            "halo {nama}, pesanan anda dengan kode {kode} telah dibatalkan. hubungi kami via whatsapp kalau ada pertanyaan.",
    ];
}

/** Membuat pesan status untuk pembeli (dipakai untuk isi WhatsApp & email). */
function pesan_status_untuk_pembeli(
    string $namaPembeli,
    string $kodePesanan,
    string $statusBaru
): string {
    $templates = template_pesan_status();

    $key = strtolower(trim($statusBaru));

    $template = $templates[$key]
        ?? "halo {nama}, status pesanan anda dengan kode {kode} telah diperbarui menjadi: {status}.";

    return strtr($template, [
        '{nama}' => $namaPembeli,
        '{kode}' => $kodePesanan,
        '{status}' => ucwords($statusBaru),
    ]);
}

/** Normalisasi nomor WhatsApp Indonesia. */
function normalisasi_nomor_wa(?string $telepon): string {
    $telepon = trim((string) $telepon);

    if ($telepon === '') {
        return '';
    }

    if (str_starts_with($telepon, '0')) {
        $telepon = '62' . ltrim($telepon, '0');
    }

    return preg_replace('/[^0-9]/', '', $telepon);
}


/** Membuat link WhatsApp dengan pesan otomatis. */
function link_wa_notifikasi_status(
    ?string $telepon,
    string $namaPembeli,
    string $kodePesanan,
    string $statusBaru
): string {
    $nomor = normalisasi_nomor_wa($telepon);

    if ($nomor === '') {
        return '';
    }

    $pesan = pesan_status_untuk_pembeli(
        $namaPembeli,
        $kodePesanan,
        $statusBaru
    );

    return 'https://wa.me/' . $nomor . '?text=' . rawurlencode($pesan);
}