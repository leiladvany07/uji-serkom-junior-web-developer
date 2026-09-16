<?php
/*
|--------------------------------------------------------------------------
| FUNGSI PENGIRIMAN EMAIL (RESEND) — dipakai bersama oleh kontak.php
| dan halaman admin/pesanan (notifikasi perubahan status).
|--------------------------------------------------------------------------
*/

if (!function_exists('kirim_email_resend')) {
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
}

if (!function_exists('get_email_template')) {
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
}