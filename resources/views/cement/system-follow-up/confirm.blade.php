<x-layouts::app :title="$isRenewal ? 'Input Data Renewal ISO' : 'Konfirmasi Tindak Lanjut ISO'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Tindak Lanjut Sertifikat"
            :title="$isRenewal ? 'Input Data Renewal ISO' : 'Konfirmasi '.$certificate->auditStageLabel()"
            :description="($certificate->isoStandard?->code ?? 'ISO').' - '.($certificate->lokasiPabrik?->nama_lokasi ?? 'Lokasi Pabrik')"
        />

        @include('admin.master-data.partials.flash-messages')
        @include('cement.maintenance.certificates.shared-errors')

        <section class="ui-form-panel">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <p class="ui-table-row-meta">Standar ISO</p>
                    <p class="ui-table-row-title">{{ $certificate->isoStandard?->code }} - {{ $certificate->isoStandard?->name }}</p>
                </div>
                <div>
                    <p class="ui-table-row-meta">Lokasi/Pabrik</p>
                    <p class="ui-table-row-title">{{ $certificate->lokasiPabrik?->nama_lokasi }}</p>
                </div>
                <div>
                    <p class="ui-table-row-meta">Tahap Saat Ini</p>
                    <p><span class="{{ $certificate->auditStageBadgeClasses() }}">{{ $certificate->auditStageLabel() }}</span></p>
                </div>
                <div>
                    <p class="ui-table-row-meta">Target Tindak Lanjut</p>
                    <p class="ui-table-row-title">{{ $certificate->followUpTargetDate()?->format('d M Y') }}</p>
                </div>
            </div>
        </section>

        @if ($isRenewal)
            <form method="POST" action="{{ route('cement.system-follow-up.store', ['certificate' => $certificate, 'action' => $action]) }}" enctype="multipart/form-data" class="ui-form-panel">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="ui-label" for="certificate_number">Nomor Sertifikat Baru</label>
                        <input id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}" class="ui-input" required>
                    </div>

                    <div class="space-y-2">
                        <label class="ui-label" for="issuer">Lembaga Sertifikasi</label>
                        <input id="issuer" name="issuer" value="{{ old('issuer', $certificate->issuer) }}" class="ui-input">
                    </div>

                    <div class="space-y-2">
                        <label class="ui-label" for="issued_at">Mulai Berlaku / Tanggal Terbit</label>
                        <input id="issued_at" name="issued_at" type="date" value="{{ old('issued_at', now()->toDateString()) }}" class="ui-input" required>
                    </div>

                    <div class="space-y-2">
                        <label class="ui-label" for="berlaku_sd">Tanggal Expired / Berlaku s.d</label>
                        <input id="berlaku_sd" name="berlaku_sd" type="date" value="{{ old('berlaku_sd') }}" class="ui-input" required>
                    </div>

                    <div class="space-y-2">
                        <label class="ui-label" for="file_sertifikat">File Sertifikat Baru</label>
                        <input id="file_sertifikat" name="file_sertifikat" type="file" class="ui-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="ui-input-hint">PDF/JPG/PNG maksimal 10 MB. File disimpan privat.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="ui-label" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="4" class="ui-textarea">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button class="ui-button-primary">Simpan Renewal</button>
                    <a href="{{ route('notifications.index') }}" class="ui-button-secondary">Kembali ke Notifikasi</a>
                </div>
            </form>
        @else
            <form method="POST" action="{{ route('cement.system-follow-up.store', ['certificate' => $certificate, 'action' => $action]) }}" enctype="multipart/form-data" class="ui-form-panel">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="ui-label" for="completed_at">Tanggal Selesai</label>
                        <input id="completed_at" name="completed_at" type="date" value="{{ old('completed_at', now()->toDateString()) }}" class="ui-input" required>
                    </div>

                    <div class="space-y-2">
                        <label class="ui-label">Tahap Berikutnya</label>
                        <div class="ui-input flex items-center">
                            {{ \App\Models\SertifikatSistemSemen::auditStageOptions()[$certificate->nextAuditStageAfterFollowUp()] }}
                        </div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="ui-label" for="notes">Catatan Tindak Lanjut</label>
                        <textarea id="notes" name="notes" rows="4" class="ui-textarea">{{ old('notes') }}</textarea>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="ui-label" for="evidence_file">Bukti Audit</label>
                        <input id="evidence_file" name="evidence_file" type="file" class="ui-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="ui-input-hint">PDF/JPG/PNG maksimal 10 MB. File disimpan privat pada timeline audit ISO.</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button class="ui-button-primary">Konfirmasi {{ $certificate->auditStageLabel() }}</button>
                    <a href="{{ route('notifications.index') }}" class="ui-button-secondary">Kembali ke Notifikasi</a>
                </div>
            </form>
        @endif
    </div>
</x-layouts::app>
