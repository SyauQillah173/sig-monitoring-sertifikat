<x-layouts::app :title="'Sertifikat Sistem'">
    <div class="ui-page ui-cement-page">
        <section class="ui-dashboard-hero">
            <div class="ui-dashboard-hero-head">
                <div>
                    <p class="ui-dashboard-kicker">Sistem Manajemen Semen</p>
                    <h1 class="ui-dashboard-title">Sertifikat Sistem ISO</h1>
                    <p class="ui-dashboard-copy">Monitoring ISO sistem manajemen yang berlaku untuk pabrik/lokasi produksi semen.</p>
                </div>
            </div>

            <section class="ui-cement-summary-grid">
                <article><span>Master ISO</span><strong>{{ $standards->count() }}</strong></article>
                <article><span>Total Sertifikat</span><strong>{{ $totalCertificates }}</strong></article>
                <article><span>Aktif</span><strong>{{ $statusSummary['aktif'] }}</strong></article>
                <article><span>Akan Berakhir</span><strong>{{ $statusSummary['akan_berakhir'] }}</strong></article>
            </section>
        </section>

        <section class="ui-system-certificate-grid">
            @forelse ($certificates as $index => $certificate)
                <article class="ui-system-certificate-card">
                    <div class="ui-system-certificate-number">{{ $index + 1 }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="ui-system-certificate-title">{{ $certificate->isoStandard?->code }}</h2>
                                <p class="ui-system-certificate-subtitle">{{ $certificate->isoStandard?->name }}</p>
                                <p class="ui-table-row-meta">{{ $certificate->lokasiPabrik?->nama_lokasi }}{{ $certificate->certification_level ? ' | '.$certificate->certificationLevelLabel() : '' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span>
                                <span class="{{ $certificate->auditStageBadgeClasses() }}">{{ $certificate->auditStageLabel() }}</span>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                            <span>Berlaku: {{ $certificate->issued_at->format('d M Y') }}</span>
                            <span>s.d</span>
                            <strong>{{ $certificate->berlaku_sd->format('d M Y') }}</strong>
                            @if ($certificate->acquisition_year)
                                <span>Tahun perolehan: {{ $certificate->acquisition_year }}</span>
                            @endif
                            <a href="{{ route('cement.certificates.document', ['type' => 'system', 'certificate' => $certificate]) }}" class="ui-button-secondary px-3 py-2 text-xs" title="Download dokumen ringkasan PDF">Dokumen</a>
                            @if ($certificate->certificateUrl() && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
                                <a href="{{ $certificate->certificateUrl() }}" class="ui-cement-link-icon" title="Download file asli">
                                    <flux:icon name="link" variant="outline" class="size-4" />
                                </a>
                            @endif
                        </div>

                        <div class="ui-system-progress">
                            <span style="width: {{ $certificate->validityProgress() }}%"></span>
                        </div>

                        <div class="mt-3 grid gap-2 text-xs text-slate-600 dark:text-slate-300 md:grid-cols-2">
                            <p><strong>Scope:</strong> {{ $certificate->scope ?: '-' }}</p>
                            <p><strong>Pemilik proses:</strong> {{ $certificate->process_owner ?: '-' }}</p>
                            <p><strong>Target tindak lanjut:</strong> {{ $certificate->followUpTargetDate()?->format('d M Y') ?: '-' }}</p>
                            <p><strong>Kategori:</strong> {{ $certificate->certification_category ?: '-' }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <article class="ui-system-certificate-card">
                    <div class="min-w-0 flex-1">
                        <h2 class="ui-system-certificate-title">Belum ada sertifikat sistem ISO</h2>
                        <p class="ui-system-certificate-subtitle">Tambahkan data dari menu Pemeliharaan Data agar dashboard ISO menampilkan monitoring per lokasi/pabrik.</p>
                    </div>
                </article>
            @endforelse
        </section>
    </div>
</x-layouts::app>
