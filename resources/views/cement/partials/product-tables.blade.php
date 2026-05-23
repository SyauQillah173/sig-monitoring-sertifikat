<section class="ui-cement-tables">
    <article class="ui-cement-table-section">
        <header class="ui-cement-table-heading is-red">
            <span>Daftar Sertifikat SNI</span>
            <strong>{{ $dashboard['sertifikatSni']->count() }}</strong>
        </header>
        <div class="ui-table-wrap ui-cement-table-scroll">
            <table class="ui-table ui-cement-table">
                <thead>
                    <tr>
                        <th>SNI</th>
                        <th>Komoditi</th>
                        <th>Merek</th>
                        <th>Jenis Sertifikasi</th>
                        <th>LSPro</th>
                        <th>Lokasi</th>
                        <th>Berlaku s.d</th>
                        <th>Status</th>
                        <th>Sertifikat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dashboard['sertifikatSni'] as $certificate)
                        <tr>
                            <td>{{ $certificate->sni }}</td>
                            <td>{{ $certificate->komoditi }}</td>
                            <td>{{ $certificate->merekSemen?->nama_merek }}</td>
                            <td>{{ $certificate->jenis_sertifikasi }}</td>
                            <td>{{ $certificate->lspro }}</td>
                            <td>{{ $certificate->lokasi }}</td>
                            <td>{{ $certificate->berlaku_sd->format('d M Y') }}</td>
                            <td><span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('cement.certificates.document', ['type' => 'sni', 'certificate' => $certificate]) }}" class="ui-button-secondary px-3 py-2 text-xs" title="Download dokumen ringkasan PDF">Dokumen</a>
                                    @if ($certificate->certificateUrl() && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
                                        <a href="{{ $certificate->certificateUrl() }}" target="_blank" class="ui-cement-link-icon" title="Download file asli">
                                            <flux:icon name="link" variant="outline" class="size-4" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-8 text-center text-slate-400">Tidak ada data Sertifikat SNI.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <article class="ui-cement-table-section">
        <header class="ui-cement-table-heading is-dark">
            <span>Daftar Sertifikat TKDN</span>
            <strong>{{ $dashboard['sertifikatTkdn']->count() }}</strong>
        </header>
        <div class="ui-table-wrap ui-cement-table-scroll">
            <table class="ui-table ui-cement-table">
                <thead>
                    <tr>
                        <th>SNI</th>
                        <th>Komoditi</th>
                        <th>Merek</th>
                        <th>% TKDN</th>
                        <th>Kemasan</th>
                        <th>Lokasi</th>
                        <th>Berlaku s.d</th>
                        <th>Status</th>
                        <th>Sertifikat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dashboard['sertifikatTkdn'] as $certificate)
                        <tr>
                            <td>{{ $certificate->sni }}</td>
                            <td>{{ $certificate->komoditi }}</td>
                            <td>{{ $certificate->merekSemen?->nama_merek }}</td>
                            <td>{{ number_format((float) $certificate->persentase_tkdn, 2, ',', '.') }}%</td>
                            <td>{{ $certificate->kemasan }}</td>
                            <td>{{ $certificate->lokasi }}</td>
                            <td>{{ $certificate->berlaku_sd->format('d M Y') }}</td>
                            <td><span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('cement.certificates.document', ['type' => 'tkdn', 'certificate' => $certificate]) }}" class="ui-button-secondary px-3 py-2 text-xs" title="Download dokumen ringkasan PDF">Dokumen</a>
                                    @if ($certificate->certificateUrl() && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
                                        <a href="{{ $certificate->certificateUrl() }}" target="_blank" class="ui-cement-link-icon" title="Download file asli">
                                            <flux:icon name="link" variant="outline" class="size-4" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-8 text-center text-slate-400">Tidak ada data Sertifikat TKDN.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <article class="ui-cement-table-section">
        <header class="ui-cement-table-heading is-red">
            <span>Daftar Sertifikat Green Label</span>
            <strong>{{ $dashboard['sertifikatGreenLabel']->count() }}</strong>
        </header>
        <div class="ui-table-wrap ui-cement-table-scroll">
            <table class="ui-table ui-cement-table">
                <thead>
                    <tr>
                        <th>SNI</th>
                        <th>Komoditi</th>
                        <th>Merek</th>
                        <th>Peringkat</th>
                        <th>Lokasi</th>
                        <th>Berlaku s.d</th>
                        <th>Status</th>
                        <th>Sertifikat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dashboard['sertifikatGreenLabel'] as $certificate)
                        <tr>
                            <td>{{ $certificate->sni }}</td>
                            <td>{{ $certificate->komoditi }}</td>
                            <td>{{ $certificate->merekSemen?->nama_merek }}</td>
                            <td>{{ $certificate->peringkat }}</td>
                            <td>{{ $certificate->lokasi }}</td>
                            <td>{{ $certificate->berlaku_sd->format('d M Y') }}</td>
                            <td><span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('cement.certificates.document', ['type' => 'green-label', 'certificate' => $certificate]) }}" class="ui-button-secondary px-3 py-2 text-xs" title="Download dokumen ringkasan PDF">Dokumen</a>
                                    @if ($certificate->certificateUrl() && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
                                        <a href="{{ $certificate->certificateUrl() }}" target="_blank" class="ui-cement-link-icon" title="Download file asli">
                                            <flux:icon name="link" variant="outline" class="size-4" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-8 text-center text-slate-400">Tidak ada data Sertifikat Green Label.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>
