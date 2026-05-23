@include('cement.maintenance.certificates.shared-errors')

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label class="ui-label" for="code">Kode ISO</label>
        <input id="code" name="code" value="{{ old('code', $standard->code) }}" class="ui-input" placeholder="ISO 9001" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="name">Nama Sistem</label>
        <input id="name" name="name" value="{{ old('name', $standard->name) }}" class="ui-input" placeholder="Sistem Manajemen Mutu" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="sort_order">Urutan</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $standard->sort_order ?? 0) }}" class="ui-input" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="ui-select">
            <option value="1" @selected((bool) old('is_active', $standard->is_active))>Aktif</option>
            <option value="0" @selected(! (bool) old('is_active', $standard->is_active))>Nonaktif</option>
        </select>
    </div>

    <div class="space-y-2 md:col-span-2">
        <label class="ui-label" for="description">Catatan</label>
        <textarea id="description" name="description" rows="4" class="ui-textarea" placeholder="Keterangan standar ISO">{{ old('description', $standard->description) }}</textarea>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button class="ui-button-primary">{{ $submitLabel }}</button>
</div>
