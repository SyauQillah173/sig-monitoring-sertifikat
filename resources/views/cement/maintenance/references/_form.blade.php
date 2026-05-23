@include('cement.maintenance.certificates.shared-errors')
<input type="hidden" name="is_active" value="0">
<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="name" class="ui-label">Nama Referensi</label>
        <input id="name" name="name" value="{{ old('name', $reference->name) }}" class="ui-input" required>
    </div>
    <div class="space-y-2">
        <label for="code" class="ui-label">Kode</label>
        <input id="code" name="code" value="{{ old('code', $reference->code) }}" class="ui-input">
    </div>
    <div class="space-y-2 md:col-span-2">
        <label for="description" class="ui-label">Catatan</label>
        <textarea id="description" name="description" rows="4" class="ui-textarea">{{ old('description', $reference->description) }}</textarea>
    </div>
    <div class="space-y-2">
        <label for="is_active" class="ui-label">Status</label>
        <select id="is_active" name="is_active" class="ui-select">
            <option value="1" @selected((bool) old('is_active', $reference->is_active))>Aktif</option>
            <option value="0" @selected(! (bool) old('is_active', $reference->is_active))>Nonaktif</option>
        </select>
    </div>
</div>
<div class="mt-6 flex flex-wrap gap-3">
    <button class="ui-button-primary">{{ $submitLabel }}</button>
</div>
