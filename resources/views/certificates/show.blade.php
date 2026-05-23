<x-layouts::app :title="'Detail Sertifikat Produk'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Monitoring Sertifikat"
            title="Detail Sertifikat Produk"
            description="Informasi lengkap sertifikat, relasi produk, dan dokumen tersimpan."
        >
            <x-slot:actions>
                @if ($certificate->hasDocument())
                    <a href="{{ route('certificates.download', $certificate) }}" class="ui-button-secondary">
                        Download Dokumen
                    </a>
                @endif
                <a href="{{ route('certificates.edit', $certificate) }}" class="ui-button-primary">
                    Edit Sertifikat
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
            <div class="ui-panel">
                <h2 class="ui-title-sm">Informasi Utama</h2>

                <dl class="ui-detail-grid mt-6">
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Nomor Sertifikat</dt>
                        <dd class="ui-detail-value font-semibold">{{ $certificate->certificate_number }}</dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Status</dt>
                        <dd class="ui-detail-value">
                            <span class="{{ $certificate->statusBadgeClasses() }}">
                                {{ $certificate->statusLabel() }}
                            </span>
                        </dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Produk</dt>
                        <dd class="ui-detail-value">{{ $certificate->product->name }}</dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Kategori Produk</dt>
                        <dd class="ui-detail-value">{{ $certificate->product->category?->name ?? '-' }}</dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Jenis Sertifikat</dt>
                        <dd class="ui-detail-value">{{ $certificate->certificateType->name }}</dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Lembaga Penerbit</dt>
                        <dd class="ui-detail-value">{{ $certificate->issuer->name }}</dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Tanggal Terbit</dt>
                        <dd class="ui-detail-value">{{ $certificate->issued_at->format('d M Y') }}</dd>
                    </div>
                    <div class="ui-detail-item">
                        <dt class="ui-detail-label">Tanggal Habis Berlaku</dt>
                        <dd class="ui-detail-value">{{ $certificate->expires_at->format('d M Y') }}</dd>
                    </div>
                    <div class="ui-detail-item md:col-span-2">
                        <dt class="ui-detail-label">Catatan</dt>
                        <dd class="ui-detail-value">{{ $certificate->notes ?: '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="space-y-6">
                <div class="ui-panel">
                    <h2 class="ui-title-sm">Dokumen</h2>
                    <div class="mt-4 space-y-3 text-sm text-slate-300">
                        <p>
                            Status dokumen:
                            <span class="font-semibold text-white">{{ $certificate->hasDocument() ? 'Tersedia' : 'Belum diunggah' }}</span>
                        </p>
                        @if ($certificate->hasDocument())
                            <p>Path tersimpan: <span class="break-all rounded bg-slate-950/70 px-2 py-1 font-mono text-xs text-slate-200">{{ $certificate->file_path }}</span></p>
                            <a href="{{ route('certificates.download', $certificate) }}" class="ui-button-primary">
                                Download File
                            </a>
                        @endif
                    </div>
                </div>

                <div class="ui-panel">
                    <h2 class="ui-title-sm">Riwayat Input</h2>
                    <dl class="mt-4 space-y-4 text-sm text-slate-300">
                        <div class="ui-detail-item">
                            <dt class="ui-detail-label">Diinput oleh</dt>
                            <dd class="ui-detail-value">{{ $certificate->issuedBy?->name ?? '-' }}</dd>
                        </div>
                        <div class="ui-detail-item">
                            <dt class="ui-detail-label">Terakhir diperbarui oleh</dt>
                            <dd class="ui-detail-value">{{ $certificate->updatedBy?->name ?? '-' }}</dd>
                        </div>
                        <div class="ui-detail-item">
                            <dt class="ui-detail-label">Dibuat pada</dt>
                            <dd class="ui-detail-value">{{ $certificate->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        <div class="ui-detail-item">
                            <dt class="ui-detail-label">Diperbarui pada</dt>
                            <dd class="ui-detail-value">{{ $certificate->updated_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
