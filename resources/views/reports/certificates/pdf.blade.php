<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Laporan Monitoring Sertifikat</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18181b; }
            h1 { margin: 0 0 8px; font-size: 20px; }
            p { margin: 0 0 6px; }
            .summary { margin: 18px 0; }
            .summary span { display: inline-block; margin-right: 16px; }
            table { width: 100%; border-collapse: collapse; margin-top: 18px; }
            th, td { border: 1px solid #d4d4d8; padding: 8px; text-align: left; vertical-align: top; }
            th { background: #f4f4f5; }
        </style>
    </head>
    <body>
        <h1>Laporan Monitoring Sertifikat</h1>
        <p>Dicetak pada: {{ now()->format('d M Y H:i') }}</p>
        <p>Periode tanggal habis berlaku: {{ $filters['date_from'] ?: '-' }} s.d. {{ $filters['date_to'] ?: '-' }}</p>

        <div class="summary">
            <span>Total: {{ $summary['total'] }}</span>
            <span>Aktif: {{ $summary['active'] }}</span>
            <span>Akan Habis: {{ $summary['expiring_soon'] }}</span>
            <span>Habis: {{ $summary['expired'] }}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nomor Sertifikat</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Penerbit</th>
                    <th>Tgl Terbit</th>
                    <th>Tgl Habis</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($certificates as $certificate)
                    <tr>
                        <td>{{ $certificate->certificate_number }}</td>
                        <td>{{ $certificate->product->name }}</td>
                        <td>{{ $certificate->product->category?->name ?? '-' }}</td>
                        <td>{{ $certificate->certificateType->name }}</td>
                        <td>{{ $certificate->issuer->name }}</td>
                        <td>{{ $certificate->issued_at->format('d M Y') }}</td>
                        <td>{{ $certificate->expires_at->format('d M Y') }}</td>
                        <td>{{ $certificate->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Tidak ada data laporan yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>
