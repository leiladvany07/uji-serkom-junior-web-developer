<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/libs/phpmailer/Exception.php';
require_once __DIR__ . '/libs/phpmailer/PHPMailer.php';
require_once __DIR__ . '/libs/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fungsi helper pembuat mailer (menghindari duplikasi kode konfigurasi SMTP)
function buat_mailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_EMAIL'] ?? '';
    $mail->Password = $_ENV['SMTP_APP_PASSWORD'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom($_ENV['SMTP_EMAIL'] ?? '', 'Lalunaco.');
    $mail->isHTML(true);
    return $mail;
}

// 1. Notifikasi untuk ADMIN
function kirim_notifikasi_admin($nama, $emailPengirim, $telepon, $pesan) {
    try {
        $mail = buat_mailer();
        $mail->addAddress($_ENV['SMTP_EMAIL'] ?? '');
        $mail->addReplyTo($emailPengirim, $nama);

        $mail->Subject = 'Pesan baru dari ' . $nama . ' — Lalunaco.';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
          @media only screen and (max-width:600px) {
            .email-wrap { width:100% !important; }
            .email-pad { padding:20px !important; }
            .email-header-pad { padding:18px 20px !important; }
          }
        </style>
        </head>
        <body style='margin:0;padding:0;background:#ffffff;'>
          <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#ffffff;'>
            <tr><td align='center' style='padding:16px 12px;'>
              <table role='presentation' class='email-wrap' width='520' cellpadding='0' cellspacing='0' style='width:520px;max-width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #E3D9C2;'>
                <tr>
                  <td class='email-header-pad' style='background:#33402C;padding:22px 28px;'>
                    <span style='font-family:Georgia,serif;color:#F3ECDD;font-size:20px;font-weight:600;'>Lalunaco<span style='color:#D9C79A;'>.</span></span>
                  </td>
                </tr>
                <tr>
                  <td class='email-pad' style='padding:28px;'>
                    <p style='margin:0 0 4px;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#A8623B;font-weight:700;'>Pesan baru masuk</p>
                    <h2 style='margin:0 0 20px;color:#2B2620;font-size:20px;'>Ada pertanyaan dari pelanggan</h2>

                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:20px;'>
                      <tr>
                        <td style='padding:6px 0;color:#786F5E;font-size:13px;width:90px;vertical-align:top;'>Nama</td>
                        <td style='padding:6px 0;color:#2B2620;font-size:14px;font-weight:600;'>{$nama}</td>
                      </tr>
                      <tr>
                        <td style='padding:6px 0;color:#786F5E;font-size:13px;vertical-align:top;'>Email</td>
                        <td style='padding:6px 0;color:#2B2620;font-size:14px;word-break:break-all;'>{$emailPengirim}</td>
                      </tr>
                      <tr>
                        <td style='padding:6px 0;color:#786F5E;font-size:13px;vertical-align:top;'>Telepon</td>
                        <td style='padding:6px 0;color:#2B2620;font-size:14px;'>" . ($telepon ?: '-') . "</td>
                      </tr>
                    </table>

                    <p style='margin:0 0 8px;color:#786F5E;font-size:13px;'>Isi pesan</p>
                    <div style='background:#F5EFE1;border-left:3px solid #A8623B;padding:14px 16px;border-radius:6px;color:#2B2620;font-size:14px;line-height:1.6;'>
                      " . nl2br(htmlspecialchars($pesan)) . "
                    </div>

                    <div style='margin-top:26px;'>
                      <a href='http://localhost:8000/admin/pesan.php' style='background:#33402C;color:#fff;text-decoration:none;padding:11px 22px;border-radius:6px;font-size:14px;font-weight:600;display:inline-block;'>Buka Dashboard Admin</a>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class='email-header-pad' style='background:#26301F;padding:16px 28px;text-align:center;'>
                    <span style='color:#D8D2C2;font-size:12px;'>Notifikasi otomatis dari website Lalunaco.</span>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>";
        $mail->AltBody = "Pesan baru dari {$nama} ({$emailPengirim}, " . ($telepon ?: '-') . "): {$pesan}";

        $mail->send();
    } catch (Exception $e) {
        error_log('Gagal kirim email notifikasi admin: ' . $e->getMessage());
    }
}

// 2. Konfirmasi untuk PELANGGAN
function kirim_konfirmasi_pelanggan($nama, $emailPelanggan, $pesan) {
    try {
        $mail = buat_mailer();
        $mail->addAddress($emailPelanggan, $nama);

        $mail->Subject = 'Pesan Anda sudah kami terima — Lalunaco.';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
          @media only screen and (max-width:600px) {
            .email-wrap { width:100% !important; }
            .email-pad { padding:20px !important; }
            .email-header-pad { padding:18px 20px !important; }
            .email-btn { display:block !important; text-align:center !important; }
          }
        </style>
        </head>
        <body style='margin:0;padding:0;background:#ffffff;'>
          <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#ffffff;'>
            <tr><td align='center' style='padding:16px 12px;'>
              <table role='presentation' class='email-wrap' width='520' cellpadding='0' cellspacing='0' style='width:520px;max-width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #E3D9C2;'>
                <tr>
                  <td class='email-header-pad' style='background:#33402C;padding:22px 28px;'>
                    <span style='font-family:Georgia,serif;color:#F3ECDD;font-size:20px;font-weight:600;'>Lalunaco<span style='color:#D9C79A;'>.</span></span>
                  </td>
                </tr>
                <tr>
                  <td class='email-pad' style='padding:28px;'>
                    <span style='display:inline-block;background:#E3ECD9;color:#26301F;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:16px;'>&#10003; Pesan diterima</span>
                    <h2 style='margin:0 0 6px;color:#2B2620;font-size:20px;'>Halo, {$nama}</h2>
                    <p style='margin:0 0 20px;color:#786F5E;font-size:14px;line-height:1.6;'>Terima kasih telah menghubungi Lalunaco. Pesan Anda sudah kami terima dan akan segera kami tindak lanjuti.</p>

                    <p style='margin:0 0 8px;color:#786F5E;font-size:13px;'>Ringkasan pesan Anda</p>
                    <div style='background:#F5EFE1;border-left:3px solid #A8623B;padding:14px 16px;border-radius:6px;color:#2B2620;font-size:14px;line-height:1.6;margin-bottom:24px;'>
                      " . nl2br(htmlspecialchars($pesan)) . "
                    </div>

                    <p style='margin:0 0 16px;color:#786F5E;font-size:14px;line-height:1.6;'>Butuh respons lebih cepat? Hubungi kami langsung lewat WhatsApp.</p>
                    <a href='https://wa.me/6288989505932' class='email-btn' style='background:#A8623B;color:#fff;text-decoration:none;padding:11px 22px;border-radius:6px;font-size:14px;font-weight:600;display:inline-block;'>Chat via WhatsApp</a>
                  </td>
                </tr>
                <tr>
                  <td class='email-header-pad' style='background:#26301F;padding:20px 28px;text-align:center;'>
                    <span style='color:#F3ECDD;font-size:13px;font-weight:600;font-family:Georgia,serif;'>Lalunaco.</span><br>
                    <span style='color:#B9AF95;font-size:11px;'>Handmade knitwear</span>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>";
        $mail->AltBody = "Halo {$nama}, terima kasih telah menghubungi Lalunaco. Pesan Anda: {$pesan}. Butuh respons cepat? WA +62 889-8950-5932.";

        $mail->send();
    } catch (Exception $e) {
        error_log('Gagal kirim email konfirmasi pelanggan: ' . $e->getMessage());
    }
}

$berhasil = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
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
  <p class="contact-lead">Ada pertanyaan seputar produk, pemesanan custom, atau kerja sama? Kirim pesan melalui form di bawah ini.</p>

  <div class="contact-layout">
    <form class="contact-form" method="post" action="kontak.php" novalidate>
      <?php if ($berhasil): ?>
        <p class="form-success">Pesan Anda sudah terkirim. Kami akan membalas secepatnya. Cek juga email Anda untuk konfirmasi.</p>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <ul class="form-errors">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <label>Nama
        <input type="text" name="nama" value="<?= h($_POST['nama'] ?? '') ?>" required>
      </label>
      <label>Email
        <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
      </label>
      <label>Telepon (opsional)
        <input type="text" name="telepon" value="<?= h($_POST['telepon'] ?? '') ?>">
      </label>
      <label>Pesan
        <textarea name="isi_pesan" rows="5" required><?= h($_POST['isi_pesan'] ?? '') ?></textarea>
      </label>
      <button type="submit" class="btn btn-primary">Kirim pesan</button>
    </form>

    <div class="contact-info">
      <div class="contact-item">
        <span class="contact-label">Alamat</span>
        <span class="contact-value">Jl. Nusa Penida, No. 27, Madiun, Jawa Timur</span>
      </div>
      <div class="contact-item">
        <span class="contact-label">Email</span>
        <span class="contact-value">leiladvany02@gmail.com</span>
      </div>
      <div class="contact-item">
        <span class="contact-label">WhatsApp</span>
        <span class="contact-value">+62 889-8950-5932</span>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>