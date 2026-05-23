@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="name" class="ui-label">Nama Lembaga</label>
        <input id="name" name="name" type="text" value="{{ old('name', $issuer->name) }}" class="ui-input" placeholder="Contoh: B4T" required>
    </div>

    <div class="space-y-2">
        <label for="code" class="ui-label">Kode</label>
        <input id="code" name="code" type="text" value="{{ old('code', $issuer->code) }}" class="ui-input" placeholder="B4T">
    </div>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="contact_person" class="ui-label">Contact Person</label>
        <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $issuer->contact_person) }}" class="ui-input" placeholder="Nama PIC">
    </div>

    <div class="space-y-2">
        <label for="email" class="ui-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $issuer->email) }}" class="ui-input" placeholder="layanan@contoh.id">
    </div>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="phone" class="ui-label">Telepon</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $issuer->phone) }}" class="ui-input" placeholder="021123456">
    </div>

    <div class="space-y-2">
        <label for="website" class="ui-label">Website</label>
        <input id="website" name="website" type="url" value="{{ old('website', $issuer->website) }}" class="ui-input" placeholder="https://contoh.id">
    </div>
</div>

<div class="mt-5 space-y-2">
    <label for="address" class="ui-label">Alamat</label>
    <textarea id="address" name="address" rows="3" class="ui-textarea" placeholder="Alamat lembaga">{{ old('address', $issuer->address) }}</textarea>
</div>

<div class="mt-5 space-y-2">
    <label for="notes" class="ui-label">Catatan</label>
    <textarea id="notes" name="notes" rows="3" class="ui-textarea" placeholder="Catatan tambahan">{{ old('notes', $issuer->notes) }}</textarea>
</div>

<div class="mt-5 space-y-2">
    <label for="is_active" class="ui-label">Status</label>
    <select id="is_active" name="is_active" class="ui-select">
        <option value="1" @selected((int) old('is_active', $issuer->is_active ?? 1) === 1)>Aktif</option>
        <option value="0" @selected((int) old('is_active', $issuer->is_active ?? 1) === 0)>Nonaktif</option>
    </select>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="ui-button-primary">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.issuers.index') }}" class="ui-button-secondary">
        Kembali
    </a>
</div>
