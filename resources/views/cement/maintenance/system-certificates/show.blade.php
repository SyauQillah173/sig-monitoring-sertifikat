<x-layouts::app :title="'Detail Sertifikat Sistem ISO'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Pemeliharaan Data"
            title="Detail Sertifikat Sistem ISO"
            description="{{ $certificate->isoStandard?->code }} - {{ $certificate->lokasiPabrik?->nama_lokasi }}"
        >
            <x-slot:actions>
                <a href="{{ route('cement.certificates.document', ['type' => 'system', 'certificate' => $certificate]) }}" class="ui-button-secondary">Download Dokumen</a>
                <a href="{{ route('cement.maintenance.sertifikat-sistem.edit', $certificate) }}" class="ui-button-primary">Edit</a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-table-shell">
            <div class="grid gap-4 p-6 md:grid-cols-2">
                <p><strong>Standar ISO:</strong> {{ $certificate->isoStandard?->code }} - {{ $certificate->isoStandard?->name }} <span class="ui-table-row-meta">ID master #{{ $certificate->iso_standard_id }}</span></p>
                <p><strong>Lokasi/Pabrik:</strong> {{ $certificate->lokasiPabrik?->nama_lokasi }} <span class="ui-table-row-meta">ID lokasi #{{ $certificate->lokasi_pabrik_id }}</span></p>
                <p><strong>Nomor Sertifikat:</strong> {{ $certificate->certificate_number }}</p>
                <p><strong>Lembaga Sertifikasi:</strong> {{ $certificate->issuer ?: '-' }}</p>
                <p><strong>Tahap Audit:</strong> <span class="{{ $certificate->auditStageBadgeClasses() }}">{{ $certificate->auditStageLabel() }}</span></p>
                <p><strong>Status:</strong> <span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span></p>
                <p><strong>Tanggal Terbit:</strong> {{ $certificate->issued_at->format('d M Y') }}</p>
                <p><strong>Berlaku s.d:</strong> {{ $certificate->berlaku_sd->format('d M Y') }}</p>
                <p><strong>Tahun Perolehan:</strong> {{ $certificate->acquisition_year ?: '-' }}</p>
                <p><strong>Level Sertifikasi:</strong> {{ $certificate->certificationLevelLabel() }}</p>
                <p><strong>Kategori:</strong> {{ $certificate->certification_category ?: '-' }}</p>
                <p><strong>Pemilik Proses:</strong> {{ $certificate->process_owner ?: '-' }}</p>
                <p><strong>Nomor Akreditasi:</strong> {{ $certificate->accreditation_number ?: '-' }}</p>
                <p><strong>Tautan Publik:</strong> @if ($certificate->public_url)<a class="ui-link" href="{{ $certificate->public_url }}" target="_blank" rel="noopener">Buka tautan</a>@else - @endif</p>
                <p><strong>Cakupan/Scope:</strong> {{ $certificate->scope ?: '-' }}</p>
                <p><strong>Progress Masa Berlaku:</strong> {{ $certificate->validityProgress() }}%</p>
                <p class="md:col-span-2"><strong>Deskripsi:</strong> {{ $certificate->description ?: '-' }}</p>
                <p class="md:col-span-2"><strong>Catatan:</strong> {{ $certificate->notes ?: '-' }}</p>
                <p><strong>File Asli:</strong> @if ($certificate->certificateUrl())<a class="ui-link" href="{{ $certificate->certificateUrl() }}" target="_blank">Download file asli</a>@else Belum diunggah @endif</p>
            </div>
        </div>

        <section class="ui-table-shell">
            <div class="p-6">
                <h2 class="ui-title-sm">Timeline Audit ISO</h2>
                <p class="ui-input-hint mt-2">Target audit dihitung semi otomatis dari tanggal terbit dan masa berlaku sertifikat. Petugas/admin tetap melakukan konfirmasi manual saat audit selesai.</p>
            </div>

            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Tahap</th>
                            <th>Target</th>
                            <th>Selesai</th>
                            <th>Status</th>
                            <th>Pelaksana</th>
                            <th>Bukti</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificate->auditEvents as $event)
                            <tr>
                                <td><p class="ui-table-row-title">{{ $event->auditTypeLabel() }}</p></td>
                                <td>{{ $event->target_date?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $event->completed_at?->format('d M Y') ?: '-' }}</td>
                                <td><span class="{{ $event->statusBadgeClasses() }}">{{ $event->statusLabel() }}</span></td>
                                <td>{{ $event->user?->name ?: '-' }}</td>
                                <td>
                                    @if ($event->evidenceUrl())
                                        <a class="ui-link" href="{{ $event->evidenceUrl() }}">Download bukti</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $event->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada timeline audit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
