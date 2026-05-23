<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reminder Sertifikat Sistem ISO</title>
</head>
<body style="margin:0;background:#eef5ec;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5ec;padding:28px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:880px;background:#ffffff;border:1px solid #d7e4d4;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="border-top:7px solid #16803b;padding:26px 30px 22px;background:#ffffff;">
                            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#16803b;font-weight:700;">SIG Monitoring Sertifikat</div>
                            <h1 style="margin:10px 0 0;font-size:25px;line-height:1.25;color:#111827;">Reminder Sertifikat - Tindak Lanjut Diperlukan</h1>
                            <p style="margin:12px 0 0;color:#475569;font-size:14px;line-height:1.6;">{{ count($certificates) }} sertifikat sistem ISO membutuhkan tindak lanjut surveilen atau renewal melalui aplikasi SIG Monitoring Sertifikat.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 30px;">
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Yth. {{ $recipientName }},</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;">Berikut daftar sertifikat sistem manajemen yang memerlukan tindak lanjut. Klik tombol pada kolom aksi untuk membuka form konfirmasi di web internal.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #b8d4bd;">
                                <thead>
                                    <tr style="background:#0f7d3b;color:#ffffff;">
                                        <th align="left" style="padding:10px 8px;font-size:12px;border:1px solid #b8d4bd;">Sistem</th>
                                        <th align="left" style="padding:10px 8px;font-size:12px;border:1px solid #b8d4bd;">Lokasi/Pabrik</th>
                                        <th align="left" style="padding:10px 8px;font-size:12px;border:1px solid #b8d4bd;">Mulai Berlaku</th>
                                        <th align="left" style="padding:10px 8px;font-size:12px;border:1px solid #b8d4bd;">Tanggal Target</th>
                                        <th align="left" style="padding:10px 8px;font-size:12px;border:1px solid #b8d4bd;">Jenis Tindak Lanjut</th>
                                        <th align="left" style="padding:10px 8px;font-size:12px;border:1px solid #b8d4bd;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($certificates as $certificate)
                                        <tr>
                                            <td style="padding:9px 8px;font-size:12px;border:1px solid #d9e7dc;">{{ $certificate['system'] }}</td>
                                            <td style="padding:9px 8px;font-size:12px;border:1px solid #d9e7dc;">{{ $certificate['lokasi'] }}</td>
                                            <td style="padding:9px 8px;font-size:12px;border:1px solid #d9e7dc;">{{ $certificate['mulai_berlaku'] }}</td>
                                            <td style="padding:9px 8px;font-size:12px;border:1px solid #d9e7dc;">{{ $certificate['target_date_label'] }}</td>
                                            <td style="padding:9px 8px;font-size:12px;border:1px solid #d9e7dc;">{{ $certificate['jenis_tindak_lanjut'] }}</td>
                                            <td style="padding:9px 8px;font-size:12px;border:1px solid #d9e7dc;">
                                                <a href="{{ $certificate['action_url'] }}" style="display:inline-block;background:#eef6ff;border:1px solid #bfdbfe;border-radius:6px;color:#1d4ed8;font-weight:700;padding:7px 9px;text-decoration:none;">
                                                    {{ $certificate['action_label'] }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div style="margin-top:22px;padding:16px 18px;background:#f8fafc;border:1px solid #dbe4f0;border-radius:10px;">
                                <p style="margin:0;font-size:13px;line-height:1.7;color:#334155;">Tombol aksi akan membuka halaman web internal dan tetap meminta login. Perubahan data tidak dilakukan otomatis dari email agar keamanan dokumen tetap terjaga.</p>
                            </div>

                            <p style="margin:20px 0 0;font-size:13px;line-height:1.7;color:#64748b;">Email ini dikirim otomatis berdasarkan pengaturan reminder pada aplikasi SIG Monitoring Sertifikat.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px;background:#f8fafc;border-top:1px solid #e5edf7;color:#64748b;font-size:12px;">
                            SIG Monitoring Sertifikat - Digitalisasi notifikasi dan tindak lanjut sertifikat sistem ISO.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
