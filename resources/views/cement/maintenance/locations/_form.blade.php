@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="nama_lokasi" class="ui-label">Nama Lokasi</label>
        <input id="nama_lokasi" name="nama_lokasi" type="text" value="{{ old('nama_lokasi', $location->nama_lokasi) }}" class="ui-input" placeholder="Contoh: Pabrik Tuban" required>
        @error('nama_lokasi')<p class="ui-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="space-y-2">
        <label for="kode" class="ui-label">Kode Lokasi</label>
        <input id="kode" name="kode" type="text" value="{{ old('kode', $location->kode) }}" class="ui-input" placeholder="Contoh: TBN">
        @error('kode')<p class="ui-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="space-y-2 md:col-span-2">
        <label for="alamat" class="ui-label">Alamat</label>
        <textarea id="alamat" name="alamat" rows="4" class="ui-textarea" placeholder="Alamat pabrik atau catatan lokasi">{{ old('alamat', $location->alamat) }}</textarea>
        @error('alamat')<p class="ui-field-error">{{ $message }}</p>@enderror
    </div>

    <label class="ui-checkline md:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $location->is_active ?? true))>
        <span>Lokasi aktif dan tampil di filter/form sertifikat</span>
    </label>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="ui-button-primary">{{ $submitLabel }}</button>
</div>
