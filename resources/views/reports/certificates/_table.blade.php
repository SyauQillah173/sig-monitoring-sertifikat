<div class="ui-table-shell">
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Nomor Sertifikat</th>
                    <th>Produk</th>
                    <th>Jenis</th>
                    <th>Penerbit</th>
                    <th>Tanggal Terbit</th>
                    <th>Tanggal Habis Berlaku</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($certificates as $certificate)
                    <tr>
                        <td>
                            <p class="ui-table-row-title">{{ $certificate->certificate_number }}</p>
                            <p class="ui-table-row-meta">{{ $certificate->product->category?->name ?? '-' }}</p>
                        </td>
                        <td>{{ $certificate->product->name }}</td>
                        <td>{{ $certificate->certificateType->name }}</td>
                        <td>{{ $certificate->issuer->name }}</td>
                        <td>{{ $certificate->issued_at->format('d M Y') }}</td>
                        <td>{{ $certificate->expires_at->format('d M Y') }}</td>
                        <td>
                            <span class="{{ $certificate->statusBadgeClasses() }}">
                                {{ $certificate->statusLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                            Tidak ada data laporan yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
