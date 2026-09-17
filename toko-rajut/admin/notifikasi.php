<?php
/**
 * Helper notifikasi status pesanan ke pembeli.
 *
 * Email: SMTP Gmail + PHPMailer.
 *   - Railway: baca SMTP_EMAIL & SMTP_APP_PASSWORD dari Service Variables.
 *   - Localhost: baca dari .env.
 * WhatsApp: membuat link wa.me dengan pesan otomatis.
 */

/** Template pesan berdasarkan status pesanan. */
function template_pesan_status(): array {
    return [
        'menunggu konfirmasi' => "Halo {nama}, pesanan Anda dengan kode {kode} sedang menunggu konfirmasi kami. Kami akan segera memprosesnya. Terima kasih telah berbelanja di Lalunaco!",
        'diproses' => "Halo {nama}, kabar baik! Pesanan Anda dengan kode {kode} sedang kami kerjakan. Kami akan kabari lagi begitu pesanan siap dikirim.",
        'dikirim' => "Halo {nama}, pesanan Anda dengan kode {kode} sudah kami kirim. Mohon ditunggu ya, semoga cepat sampai dan sesuai harapan!",
        'selesai' => "Halo {nama}, pesanan Anda dengan kode {kode} telah selesai. Terima kasih sudah berbelanja di Lalunaco, semoga suka dengan produknya!",
        'dibatalkan' => "Halo {nama}, mohon maaf pesanan Anda dengan kode {kode} telah dibatalkan. Jika ada pertanyaan atau ini keliru, silakan hubungi kami.",
    ];
}

/** Membuat pesan status untuk pembeli. */
function pesan_status_untuk_pembeli(string $namaPembeli, string $kodePesanan, string $statusBaru): string {
    $templates = template_pesan_status();
    $key = mb_strtolower(trim($statusBaru));
    $template = $templates[$key] ?? "Halo {nama}, status pesanan Anda dengan kode {kode} telah diperbarui menjadi: {status}.";

    return strtr($template, [
        '{nama}' => $namaPembeli,
        '{kode}' => $kodePesanan,
        '{status}' => ucwords($statusBaru),
    ]);
}

/** Normalisasi nomor WhatsApp Indonesia. */
function normalisasi_nomor_wa(?string $telepon): string {
    $telepon = trim((string) $telepon);
    if ($telepon === '') return '';
    if (str_starts_with($telepon, '0')) $telepon = '62' . ltrim($telepon, '0');
    return preg_replace('/[^0-9]/', '', $telepon);
}

/** Membuat link WhatsApp dengan pesan otomatis. */
function link_wa_notifikasi_status(?string $telepon, string $namaPembeli, string $kodePesanan, string $statusBaru): string {
    $nomor = normalisasi_nomor_wa($telepon);
    if ($nomor === '') return '';

    $pesan = pesan_status_untuk_pembeli($namaPembeli, $kodePesanan, $statusBaru);
    return 'https://wa.me/' . $nomor . '?text=' . rawurlencode($pesan);
}

/** Template email Lalunaco. */
if (!function_exists('template_email_notifikasi')) {
    function template_email_notifikasi(string $title, string $bodyContent): string {
        return "<!DOCTYPE html>
        <html>
        <head><meta name='viewport' content='width=device-width, initial-scale=1.0'></head>
        <body style='margin:0;padding:0;background:#ffffff;'>
          <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#ffffff;'>
            <tr><td align='center' style='padding:16px 12px;'>
              <table role='presentation' width='520' cellpadding='0' cellspacing='0' style='width:520px;max-width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #E3D9C2;'>
                <tr><td style='background:#33402C;padding:22px 28px;'>
                  <span style='font-family:Georgia,serif;color:#F3ECDD;font-size:20px;font-weight:600;'>Lalunaco<span style='color:#D9C79A;'>.</span></span>
                </td></tr>
                <tr><td style='padding:28px;'>{$bodyContent}</td></tr>
                <tr><td style='background:#26301F;padding:16px 28px;text-align:center;'>
                  <span style='color:#D8D2C2;font-size:12px;'>{$title}</span>
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>";
    }
}

/**
 * Kirim email status pesanan ke pembeli.
 * Railway: getenv('SMTP_EMAIL') / getenv('SMTP_APP_PASSWORD')
 * Localhost: $_ENV dari .env
 */
function kirim_email_status_pesanan(?string $emailTujuan, string $namaPembeli, string $kodePesanan, string $statusBaru, ?string &$errorOut = null): bool {
    $emailTujuan = trim((string) $emailTujuan);

    if ($emailTujuan === '') {
        $errorOut = 'Pesanan ini tidak mencantumkan alamat email pembeli.';
        return false;
    }
    if (!filter_var($emailTujuan, FILTER_VALIDATE_EMAIL)) {
        $errorOut = 'Alamat email pembeli tidak valid: ' . $emailTujuan;
        return false;
    }

    // Load .env untuk localhost (aman jika Railway tidak punya file ini, karena getenv() pakai Service Variables)
    $envFile = __DIR__ . '/../env.php';
    if (file_exists($envFile)) require_once $envFile;

    // Ambil konfigurasi SMTP — prioritas: getenv() (Railway) lalu $_ENV (.env lokal)
    $smtpUser = getenv('SMTP_EMAIL') ?: ($_ENV['SMTP_EMAIL'] ?? '');
    $smtpPass = getenv('SMTP_APP_PASSWORD') ?: ($_ENV['SMTP_APP_PASSWORD'] ?? '');

    if ($smtpUser === '' || $smtpPass === '') {
        $errorOut = 'Konfigurasi SMTP belum tersedia. Pastikan SMTP_EMAIL dan SMTP_APP_PASSWORD sudah diatur di Railway Variables atau file .env.';
        return false;
    }

    $libsPhpMailer = __DIR__ . '/../libs/phpmailer/';
    if (!file_exists($libsPhpMailer . 'PHPMailer.php')) {
        $errorOut = 'File PHPMailer tidak ditemukan di libs/phpmailer/.';
        return false;
    }

    require_once $libsPhpMailer . 'Exception.php';
    require_once $libsPhpMailer . 'SMTP.php';
    require_once $libsPhpMailer . 'PHPMailer.php';

    $pesanTeks = pesan_status_untuk_pembeli($namaPembeli, $kodePesanan, $statusBaru);
    $subjek = "Update Pesanan {$kodePesanan} - Lalunaco";

    $n = htmlspecialchars($namaPembeli, ENT_QUOTES, 'UTF-8');
    $kodeAman = htmlspecialchars($kodePesanan, ENT_QUOTES, 'UTF-8');
    $statusTampil = htmlspecialchars(ucwords($statusBaru), ENT_QUOTES, 'UTF-8');
    $pesanHtml = nl2br(htmlspecialchars($pesanTeks, ENT_QUOTES, 'UTF-8'));

    $content = "
        <span style='display:inline-block;background:#EFE3C8;color:#7C4527;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:16px;'>Update pesanan</span>
        <h2 style='margin:0 0 6px;color:#2B2620;font-size:20px;'>Halo, {$n}</h2>
        <p style='margin:0 0 20px;color:#786F5E;font-size:14px;line-height:1.6;'>{$pesanHtml}</p>
        <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:20px;'>
          <tr>
            <td style='padding:6px 0;color:#786F5E;font-size:13px;width:110px;'>Kode Pesanan</td>
            <td style='padding:6px 0;color:#2B2620;font-size:14px;font-weight:600;'>{$kodeAman}</td>
          </tr>
          <tr>
            <td style='padding:6px 0;color:#786F5E;font-size:13px;'>Status</td>
            <td style='padding:6px 0;'>
              <span style='background:#EFE3C8;color:#7C4527;padding:4px 10px;border-radius:12px;font-size:13px;font-weight:700;'>{$statusTampil}</span>
            </td>
          </tr>
        </table>
        <a href='https://wa.me/6288989505932' style='background:#A8623B;color:#fff;text-decoration:none;padding:11px 22px;border-radius:6px;font-size:14px;font-weight:600;display:inline-block;'>Chat via WhatsApp</a>
    ";

    $html = template_email_notifikasi('Lalunaco. — Handmade knitwear', $content);

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtpUser, 'Lalunaco');
        $mail->addAddress($emailTujuan, $namaPembeli);

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body = $html;
        $mail->AltBody = $pesanTeks;

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        $errorOut = 'PHPMailer gagal mengirim: ' . $e->getMessage();
        error_log('PHPMailer Error (notifikasi status pesanan): ' . $e->getMessage());
        return false;
    }
}