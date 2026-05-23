@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="name" class="ui-label">Nama Jenis Sertifikat</label>
        <input id="name" name="name" type="text" value="{{ old('name', $certificateType->name) }}" class="ui-input" placeholder="Contoh: Sertifikat SNI" required>
    </div>

    <div class="space-y-2">
        <label for="slug" class="ui-label">Slug</label>
        <input id="slug" name="slug" type="text" value="{{ old('slug', $certificateType->slug) }}" class="ui-input" placeholder="sertifikat-sni" required>
    </div>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="renewal_period_days" class="ui-label">Periode Perpanjangan (hari)</label>
        <input id="renewal_period_days" name="renewal_period_days" type="number" min="1" value="{{ old('renewal_period_days', $certificateType->renewal_period_days) }}" class="ui-input" placeholder="365">
    </div>

    <div class="space-y-2">
        <label for="is_active" class="ui-label">Status</label>
        <select id="is_active" name="is_active" class="ui-select">
            <option value="1" @selected((int) old('is_active', $certificateType->is_active ?? 1) === 1)>Aktif</option>
            <option value="0" @selected((int) old('is_active', $certificateType->is_active ?? 1) === 0)>Nonaktif</option>
        </select>
    </div>
</div>

<div class="mt-5 space-y-2">
    <label for="description" class="ui-label">Deskripsi</label>
    <textarea id="description" name="description" rows="4" class="ui-textarea" placeholder="Deskripsi singkat jenis sertifikat">{{ old('description', $certificateType->description) }}</textarea>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="ui-button-primary">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.certificate-types.index') }}" class="ui-button-secondary">
        Kembali
    </a>
</div>
