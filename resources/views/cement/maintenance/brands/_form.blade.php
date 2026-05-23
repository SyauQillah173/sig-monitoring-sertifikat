@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="kategori_semen_id" class="ui-label">Kategori Semen</label>
        <select id="kategori_semen_id" name="kategori_semen_id" class="ui-select" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('kategori_semen_id', $brand->kategori_semen_id) === $category->id)>{{ $category->nama_kategori }}</option>
            @endforeach
        </select>
        @error('kategori_semen_id')<p class="ui-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="space-y-2">
        <label for="nama_merek" class="ui-label">Nama Merek Semen</label>
        <input id="nama_merek" name="nama_merek" type="text" value="{{ old('nama_merek', $brand->nama_merek) }}" class="ui-input" placeholder="Contoh: Semen Gresik" required>
        @error('nama_merek')<p class="ui-field-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="ui-button-primary">{{ $submitLabel }}</button>
</div>
