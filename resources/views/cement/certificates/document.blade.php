<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] }}</title>
    <style>
        @page { margin: 0; size: A4 portrait; }
        html, body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }
        .page {
            height: 297mm;
            overflow: hidden;
            position: relative;
            width: 210mm;
        }
        .background {
            height: 297mm;
            left: 0;
            position: absolute;
            top: 0;
            width: 210mm;
            z-index: 0;
        }
        .content {
            height: 297mm;
            left: 0;
            position: absolute;
            top: 0;
            width: 210mm;
            z-index: 2;
        }
        .issuer {
            color: #12356f;
            font-size: 10.8px;
            font-weight: 700;
            left: 34mm;
            letter-spacing: 1.6px;
            line-height: 1.45;
            position: absolute;
            right: 34mm;
            text-align: center;
            text-transform: uppercase;
            top: 30mm;
        }
        .title {
            color: #0f172a;
            font-size: 22.4px;
            font-weight: 800;
            left: 12mm;
            letter-spacing: 1.2px;
            line-height: 1.12;
            position: absolute;
            right: 12mm;
            text-align: center;
            text-transform: uppercase;
            top: 57mm;
            white-space: nowrap;
        }
        .subtitle {
            color: #1e3a8a;
            font-size: 13px;
            font-weight: 700;
            left: 30mm;
            letter-spacing: .6px;
            position: absolute;
            right: 30mm;
            text-align: center;
            text-transform: uppercase;
            top: 70mm;
        }
        .number {
            color: #334155;
            font-size: 11.4px;
            font-weight: 600;
            left: 30mm;
            position: absolute;
            right: 30mm;
            text-align: center;
            top: 81mm;
        }
        .lead {
            color: #475569;
            font-size: 11px;
            left: 32mm;
            position: absolute;
            top: 95mm;
        }
        .data {
            border-collapse: collapse;
            left: 32mm;
            position: absolute;
            top: 103mm;
            width: 148mm;
        }
        .data th,
        .data td {
            border: 0;
            padding: 3.8px 4px;
            vertical-align: top;
        }
        .data th {
            color: #334155;
            font-size: 10.4px;
            font-weight: 700;
            text-align: left;
            width: 45mm;
        }
        .data .colon {
            color: #334155;
            font-size: 10.4px;
            text-align: center;
            width: 4mm;
        }
        .data td {
            color: #111827;
            font-size: 11px;
            font-weight: 600;
        }
        .notice {
            color: #475569;
            font-size: 8.6px;
            left: 30mm;
            line-height: 1.5;
            position: absolute;
            right: 30mm;
            text-align: center;
            top: 203mm;
        }
        .signature-block {
            color: #0f172a;
            font-size: 9.8px;
            font-weight: 700;
            position: absolute;
            text-align: center;
            top: 262mm;
            width: 62mm;
        }
        .signature-block .role {
            color: #334155;
            font-size: 8.8px;
            font-weight: 600;
            margin-bottom: 9mm;
        }
        .signature-block .name {
            color: #0f172a;
            font-size: 10.2px;
            font-weight: 800;
        }
        .signature-left {
            left: 30mm;
        }
        .signature-right {
            right: 30mm;
        }
        .footer {
            bottom: 11mm;
            color: #475569;
            font-size: 7.4px;
            left: 24mm;
            letter-spacing: .2px;
            position: absolute;
            right: 24mm;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="page">
        <img class="background" src="{{ $document['template_src'] }}" alt="">

        <section class="content">
            <div class="issuer">
                PT Semen Indonesia Group<br>
                Internal Monitoring Platform<br>
                Sistem Monitoring Sertifikat Produk dan Sistem Semen
            </div>

            <div class="title">{{ $document['title'] }}</div>
            <div class="subtitle">{{ $document['subtitle'] }}</div>
            <div class="number">No : {{ $document['number'] }}</div>

            <div class="lead">Dokumen ringkasan ini diberikan kepada data/subjek berikut:</div>

            <table class="data">
                @foreach ($document['rows'] as $row)
                    <tr>
                        <th>{{ $row[0] }}</th>
                        <td class="colon">:</td>
                        <td>{{ $row[1] }}</td>
                    </tr>
                @endforeach
            </table>

            <div class="notice">
                Dokumen ini adalah ringkasan monitoring internal yang dibuat otomatis dari database SIG Monitoring Sertifikat.
                Dokumen ini bukan pengganti sertifikat resmi dari lembaga penerbit. Untuk pembuktian formal, gunakan file sertifikat asli yang diunggah pada sistem.
            </div>

            <div class="signature-block signature-left">
                <div class="role">Kepala SIG</div>
                <div class="name">SIG Monitoring</div>
            </div>

            <div class="signature-block signature-right">
                <div class="role">Pemilik Sertifikat</div>
                <div class="name">{{ $document['owner'] }}</div>
            </div>

            <div class="footer">
                Dokumen internal dan terkendali | Dicetak {{ now()->format('d M Y H:i') }} WIB
            </div>
        </section>
    </main>
</body>
</html>
