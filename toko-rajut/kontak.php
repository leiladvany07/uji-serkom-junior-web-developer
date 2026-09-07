<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/env.php';

/*
|--------------------------------------------------------------------------
| FUNGSI PENGIRIMAN EMAIL (RESEND)
|--------------------------------------------------------------------------
*/
function kirim_email_resend($to, $subject, $html, $replyTo = null) {
    $apiKey = $_ENV['RESEND_API_KEY'] ?? '';
    $from   = $_ENV['RESEND_FROM_EMAIL'] ?? '';

    if ($apiKey === '' || $from === '') {
        error_log('Resend belum dikonfigurasi.');
        return false;
    }

    $data = [
        'from'    => $from,
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $html
    ];

    if (!empty($replyTo)) {
        $data['reply_to'] = [$replyTo];
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 20
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
        error_log('Resend Error: ' . ($curlError ?: $response));
        return false;
    }

    return true;
}

function get_email_template($title, $bodyContent) {
    return "<!DOCTYPE html>
    <html>
    <head><meta name='viewport' content='width=device-width, initial-scale=1.0'></head>
    <body style='margin:0;padding:0;background:#ffffff;'>
        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#ffffff;'>
            <tr><td align='center' style='padding:16px 12px;'>
                <table role='presentation' width='520' cellpadding='0' cellspacing='0' style='width:520px;max-width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #E3D9C2;'>
                    <tr><td style='background:#33402C;padding:22px 28px;'><span style='font-family:Georgia,serif;color:#F3ECDD;font-size:20px;font-weight:600;'>Lalunaco<span style='color:#D9C79A;'>.</span></span></td></tr>
                    <tr><td style='padding:28px;'>{$bodyContent}</td></tr>
                    <tr><td style='background:#26301F;padding:16px 28px;text-align:center;'><span style='color:#D8D2C2;font-size:12px;'>{$title}</span></td></tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>";
}

function kirim_notifikasi_admin($nama, $emailPengirim, $telepon, $pesan) {
    $emailAdmin = $_ENV['RESEND_TO_EMAIL'] ?? $_ENV['SMTP_EMAIL'] ?? '';
    if ($emailAdmin === '') return false;

    $n = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
    $e = htmlspecialchars($emailPengirim, ENT_QUOTES, 'UTF-8');
    $t = htmlspecialchars($telepon ?: '-', ENT_QUOTES, 'UTF-8');
    $p = nl2br(htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'));

    $content = "
        <p style='margin:0 0 4px;font-size:12px;text-transform:uppercase;color:#A8623B;font-weight:700;'>Pesan baru masuk</p>
        <h2 style='margin:0 0 20px;color:#2B2620;font-size:20px;'>Ada pertanyaan dari pelanggan</h2>
        <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:20px;'>
            <tr><td style='padding:6px 0;color:#786F5E;font-size:13px;width:90px;'>Nama</td><td style='padding:6px 0;color:#2B2620;font-size:14px;font-weight:600;'>{$n}</td></tr>
            <tr><td style='padding:6px 0;color:#786F5E;font-size:13px;'>Email</td><td style='padding:6px 0;color:#2B2620;font-size:14px;word-break:break-all;'>{$e}</td></tr>
            <tr><td style='padding:6px 0;color:#786F5E;font-size:13px;'>Telepon</td><td style='padding:6px 0;color:#2B2620;font-size:14px;'>{$t}</td></tr>
        </table>
        <p style='margin:0 0 8px;color:#786F5E;font-size:13px;'>Isi pesan</p>
        <div style='background:#F5EFE1;border-left:3px solid #A8623B;padding:14px 16px;border-radius:6px;color:#2B2620;font-size:14px;line-height:1.6;'>{$p}</div>
        <div style='margin-top:26px;'><a href='http://localhost:8000/admin/pesan.php' style='background:#33402C;color:#fff;text-decoration:none;padding:11px 22px;border-radius:6px;font-size:14px;font-weight:600;display:inline-block;'>Buka Dashboard Admin</a></div>
    ";

    return kirim_email_resend($emailAdmin, 'Pesan baru dari ' . $nama . ' — Lalunaco.', get_email_template('Notifikasi otomatis dari website Lalunaco.', $content), $emailPengirim);
}

function kirim_konfirmasi_pelanggan($nama, $emailPelanggan, $pesan) {
    $n = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
    $p = nl2br(htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'));

    $content = "
        <span style='display:inline-block;background:#E3ECD9;color:#26301F;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:16px;'>&#10003; Pesan diterima</span>
        <h2 style='margin:0 0 6px;color:#2B2620;font-size:20px;'>Halo, {$n}</h2>
        <p style='margin:0 0 20px;color:#786F5E;font-size:14px;line-height:1.6;'>Terima kasih telah menghubungi Lalunaco. Pesan Anda sudah kami terima dan akan segera ditindaklanjuti.</p>
        <p style='margin:0 0 8px;color:#786F5E;font-size:13px;'>Ringkasan pesan Anda</p>
        <div style='background:#F5EFE1;border-left:3px solid #A8623B;padding:14px 16px;border-radius:6px;color:#2B2620;font-size:14px;line-height:1.6;margin-bottom:24px;'>{$p}</div>
        <p style='margin:0 0 16px;color:#786F5E;font-size:14px;'>Butuh respons lebih cepat? Hubungi kami lewat WhatsApp.</p>
        <a href='https://wa.me/6288989505932' style='background:#A8623B;color:#fff;text-decoration:none;padding:11px 22px;border-radius:6px;font-size:14px;font-weight:600;display:inline-block;'>Chat via WhatsApp</a>
    ";

    return kirim_email_resend($emailPelanggan, 'Pesan Anda sudah kami terima — Lalunaco.', get_email_template('Lalunaco. — Handmade knitwear', $content));
}

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/
$berhasil = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telepon  = trim($_POST['telepon'] ?? '');
    $isiPesan = trim($_POST['isi_pesan'] ?? '');

    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if ($isiPesan === '') $errors[] = 'Pesan tidak boleh kosong.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO pesan (nama, email, telepon, isi_pesan) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $email, $telepon, $isiPesan]);

        $berhasil = true;
        kirim_notifikasi_admin($nama, $email, $telepon, $isiPesan);
        kirim_konfirmasi_pelanggan($nama, $email, $isiPesan);
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
                    Pesan Anda sudah terkirim. Kami akan membalas secepatnya. Cek juga email Anda untuk konfirmasi.
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