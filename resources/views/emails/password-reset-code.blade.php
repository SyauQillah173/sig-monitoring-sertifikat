<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode Reset Password SIG</title>
</head>
<body style="margin:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb;padding:28px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dbe4f0;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#10233f;padding:24px 28px;color:#ffffff;">
                            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#8de6d1;font-weight:700;">SIG Monitoring Sertifikat</div>
                            <h1 style="margin:10px 0 0;font-size:24px;line-height:1.25;">Kode reset password</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Halo {{ $user->name }},</p>
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Gunakan kode berikut untuk melanjutkan proses penggantian password akun SIG Monitoring Sertifikat Anda.</p>
                            <div style="margin:24px 0;padding:22px;background:#f8fafc;border:1px solid #dbe4f0;border-radius:12px;text-align:center;">
                                <div style="font-size:13px;color:#64748b;">Kode verifikasi</div>
                                <div style="margin-top:8px;font-size:34px;letter-spacing:8px;font-weight:800;color:#10233f;">{{ $code }}</div>
                            </div>
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#334155;">Kode ini berlaku {{ $expiresInMinutes }} menit. Abaikan email ini jika Anda tidak meminta reset password.</p>
                            <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">Demi keamanan, jangan bagikan kode ini kepada siapa pun.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#f8fafc;border-top:1px solid #e5edf7;color:#64748b;font-size:12px;">
                            SIG Monitoring Sertifikat - Sistem monitoring dokumen dan masa berlaku sertifikat.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
