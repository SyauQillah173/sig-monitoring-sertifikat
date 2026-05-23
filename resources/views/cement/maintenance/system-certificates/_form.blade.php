@include('cement.maintenance.certificates.shared-errors')

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label class="ui-label" for="lokasi_pabrik_id">Lokasi/Pabrik</label>
        <select id="lokasi_pabrik_id" name="lokasi_pabrik_id" class="ui-select" required>
            <option value="">Pilih lokasi pabrik</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected((int) old('lokasi_pabrik_id', $certificate->lokasi_pabrik_id) === $location->id)>
                    {{ $location->nama_lokasi }}
                </option>
            @endforeach
        </select>
        <p class="ui-input-hint">Sertifikat sistem ISO ditempel ke pabrik/lokasi semen.</p>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="iso_standard_id">Standar ISO</label>
        <select id="iso_standard_id" name="iso_standard_id" class="ui-select" required>
            <option value="">Pilih standar ISO</option>
            @foreach ($standards as $standard)
                <option value="{{ $standard->id }}" @selected((int) old('iso_standard_id', $certificate->iso_standard_id) === $standard->id)>
                    {{ $standard->code }} - {{ $standard->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="certificate_number">Nomor Sertifikat</label>
        <input id="certificate_number" name="certificate_number" value="{{ old('certificate_number', $certificate->certificate_number) }}" class="ui-input" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="issuer">Lembaga Sertifikasi</label>
        <input id="issuer" name="issuer" value="{{ old('issuer', $certificate->issuer) }}" class="ui-input" placeholder="Nama lembaga sertifikasi">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="acquisition_year">Tahun Perolehan</label>
        <input id="acquisition_year" name="acquisition_year" type="number" min="1900" max="2100" value="{{ old('acquisition_year', $certificate->acquisition_year) }}" class="ui-input" placeholder="2024">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="certification_level">Level Sertifikasi</label>
        <select id="certification_level" name="certification_level" class="ui-select">
            <option value="">Pilih level</option>
            @foreach ($certificationLevels as $value => $label)
                <option value="{{ $value }}" @selected(old('certification_level', $certificate->certification_level) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="certification_category">Kategori Sertifikasi</label>
        <input id="certification_category" name="certification_category" value="{{ old('certification_category', $certificate->certification_category) }}" class="ui-input" placeholder="Sistem Manajemen Mutu">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="process_owner">Pemilik Proses</label>
        <input id="process_owner" name="process_owner" value="{{ old('process_owner', $certificate->process_owner) }}" class="ui-input" placeholder="Management System / QA / GRC">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="accreditation_number">Nomor Akreditasi</label>
        <input id="accreditation_number" name="accreditation_number" value="{{ old('accreditation_number', $certificate->accreditation_number) }}" class="ui-input" placeholder="Opsional">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="public_url">Tautan Publik</label>
        <input id="public_url" name="public_url" type="url" value="{{ old('public_url', $certificate->public_url) }}" class="ui-input" placeholder="https://...">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="audit_stage">Tahap Audit</label>
        <select id="audit_stage" name="audit_stage" class="ui-select" required>
            @foreach ($auditStages as $value => $label)
                <option value="{{ $value }}" @selected(old('audit_stage', $certificate->audit_stage) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="scope">Cakupan/Scope</label>
        <input id="scope" name="scope" value="{{ old('scope', $certificate->scope) }}" class="ui-input" placeholder="Produksi semen">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="issued_at">Tanggal Terbit</label>
        <input id="issued_at" name="issued_at" type="date" value="{{ old('issued_at', optional($certificate->issued_at)->format('Y-m-d')) }}" class="ui-input" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="berlaku_sd">Berlaku s.d</label>
        <input id="berlaku_sd" name="berlaku_sd" type="date" value="{{ old('berlaku_sd', optional($certificate->berlaku_sd)->format('Y-m-d')) }}" class="ui-input" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="file_sertifikat">File Sertifikat</label>
        <input id="file_sertifikat" name="file_sertifikat" type="file" class="ui-input" accept=".pdf,.jpg,.jpeg,.png">
        <p class="ui-input-hint">PDF/JPG/PNG maksimal 10 MB. File disimpan privat dan hanya bisa dibuka lewat login.</p>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="notes">Catatan</label>
        <textarea id="notes" name="notes" rows="4" class="ui-textarea">{{ old('notes', $certificate->notes) }}</textarea>
    </div>

    <div class="space-y-2 md:col-span-2">
        <label class="ui-label" for="description">Deskripsi Sertifikasi</label>
        <textarea id="description" name="description" rows="4" class="ui-textarea" placeholder="Deskripsi/ruang lingkup korporasi seperti struktur sertifikasi SIG.">{{ old('description', $certificate->description) }}</textarea>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button class="ui-button-primary">{{ $submitLabel }}</button>
</div>
