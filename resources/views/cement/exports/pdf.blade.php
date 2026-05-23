<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Sertifikat Semen</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        h2 { margin: 18px 0 8px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; }
        th { background: #ef3340; color: white; }
        .dark th { background: #374151; }
    </style>
</head>
<body>
    <h1>Laporan Monitoring Sertifikat Produk Semen</h1>
    <p>Total SNI: {{ $sertifikatSni->count() }} | TKDN: {{ $sertifikatTkdn->count() }} | Green Label: {{ $sertifikatGreenLabel->count() }}</p>

    <h2>Sertifikat SNI</h2>
    <table><thead><tr><th>SNI</th><th>Komoditi</th><th>Merek</th><th>Jenis</th><th>LSPro</th><th>Lokasi</th><th>Berlaku</th><th>Status</th></tr></thead><tbody>
        @foreach($sertifikatSni as $certificate)<tr><td>{{ $certificate->sni }}</td><td>{{ $certificate->komoditi }}</td><td>{{ $certificate->merekSemen?->nama_merek }}</td><td>{{ $certificate->jenis_sertifikasi }}</td><td>{{ $certificate->lspro }}</td><td>{{ $certificate->lokasi }}</td><td>{{ $certificate->berlaku_sd->format('Y-m-d') }}</td><td>{{ $certificate->statusLabel() }}</td></tr>@endforeach
    </tbody></table>

    <h2>Sertifikat TKDN</h2>
    <table class="dark"><thead><tr><th>SNI</th><th>Komoditi</th><th>Merek</th><th>% TKDN</th><th>Kemasan</th><th>Lokasi</th><th>Berlaku</th><th>Status</th></tr></thead><tbody>
        @foreach($sertifikatTkdn as $certificate)<tr><td>{{ $certificate->sni }}</td><td>{{ $certificate->komoditi }}</td><td>{{ $certificate->merekSemen?->nama_merek }}</td><td>{{ $certificate->persentase_tkdn }}%</td><td>{{ $certificate->kemasan }}</td><td>{{ $certificate->lokasi }}</td><td>{{ $certificate->berlaku_sd->format('Y-m-d') }}</td><td>{{ $certificate->statusLabel() }}</td></tr>@endforeach
    </tbody></table>

    <h2>Sertifikat Green Label</h2>
    <table><thead><tr><th>SNI</th><th>Komoditi</th><th>Merek</th><th>Peringkat</th><th>Lokasi</th><th>Berlaku</th><th>Status</th></tr></thead><tbody>
        @foreach($sertifikatGreenLabel as $certificate)<tr><td>{{ $certificate->sni }}</td><td>{{ $certificate->komoditi }}</td><td>{{ $certificate->merekSemen?->nama_merek }}</td><td>{{ $certificate->peringkat }}</td><td>{{ $certificate->lokasi }}</td><td>{{ $certificate->berlaku_sd->format('Y-m-d') }}</td><td>{{ $certificate->statusLabel() }}</td></tr>@endforeach
    </tbody></table>
</body>
</html>
