@csrf

<div class="space-y-2">
    <label for="nama_kategori" class="ui-label">Nama Kategori Semen</label>
    <input id="nama_kategori" name="nama_kategori" type="text" value="{{ old('nama_kategori', $category->nama_kategori) }}" class="ui-input" placeholder="Contoh: Semen Portland Komposit (PCC)" required>
    @error('nama_kategori')<p class="ui-field-error">{{ $message }}</p>@enderror
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="ui-button-primary">{{ $submitLabel }}</button>
</div>
