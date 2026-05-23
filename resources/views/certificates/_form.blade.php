@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="product_id" class="ui-label">Produk</label>
        <select id="product_id" name="product_id" class="ui-select" required>
            <option value="">Pilih produk</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((int) old('product_id', $certificate->product_id) === $product->id)>
                    {{ $product->name }} - {{ $product->category?->name ?? 'Tanpa Kategori' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label for="certificate_type_id" class="ui-label">Jenis Sertifikat</label>
        <select id="certificate_type_id" name="certificate_type_id" class="ui-select" required>
            <option value="">Pilih jenis sertifikat</option>
            @foreach ($certificateTypes as $certificateType)
                <option value="{{ $certificateType->id }}" @selected((int) old('certificate_type_id', $certificate->certificate_type_id) === $certificateType->id)>
                    {{ $certificateType->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="issuer_id" class="ui-label">Lembaga Penerbit</label>
        <select id="issuer_id" name="issuer_id" class="ui-select" required>
            <option value="">Pilih lembaga penerbit</option>
            @foreach ($issuers as $issuer)
                <option value="{{ $issuer->id }}" @selected((int) old('issuer_id', $certificate->issuer_id) === $issuer->id)>
                    {{ $issuer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label for="certificate_number" class="ui-label">Nomor Sertifikat</label>
        <input
            id="certificate_number"
            name="certificate_number"
            type="text"
            value="{{ old('certificate_number', $certificate->certificate_number) }}"
            class="ui-input"
            placeholder="Contoh: CERT-2026-0001"
            required
        >
    </div>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="issue_date" class="ui-label">Tanggal Terbit</label>
        <input
            id="issue_date"
            name="issue_date"
            type="date"
            value="{{ old('issue_date', $certificate->issued_at?->format('Y-m-d')) }}"
            class="ui-input"
            required
        >
    </div>

    <div class="space-y-2">
        <label for="expiry_date" class="ui-label">Tanggal Habis Berlaku</label>
        <input
            id="expiry_date"
            name="expiry_date"
            type="date"
            value="{{ old('expiry_date', $certificate->expires_at?->format('Y-m-d')) }}"
            class="ui-input"
            required
        >
    </div>
</div>

<div class="mt-5 space-y-2">
    <label for="document" class="ui-label">Scan Sertifikat</label>
    <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="ui-file">
    <p class="ui-input-hint">
        Format file: PDF, JPG, JPEG, PNG. Maksimal 5 MB.
    </p>
    @if ($certificate->hasDocument())
        <a href="{{ route('certificates.download', $certificate) }}" class="inline-flex items-center text-sm font-semibold text-teal-100 underline underline-offset-4 transition hover:text-white">
            Download dokumen saat ini
        </a>
    @endif
</div>

<div class="mt-5 space-y-2">
    <label for="notes" class="ui-label">Catatan</label>
    <textarea
        id="notes"
        name="notes"
        rows="4"
        class="ui-textarea"
        placeholder="Catatan tambahan terkait sertifikat"
    >{{ old('notes', $certificate->notes) }}</textarea>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="ui-button-primary">
        {{ $submitLabel }}
    </button>

    <a href="{{ route('certificates.index') }}" class="ui-button-secondary">
        Kembali
    </a>
</div>
