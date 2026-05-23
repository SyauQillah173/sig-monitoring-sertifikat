<div class="space-y-2">
    <label class="ui-label" for="merek_semen_id">Merek Semen</label>
    <select id="merek_semen_id" name="merek_semen_id" class="ui-select" required>
        <option value="">Pilih merek semen</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}" @selected((int) old('merek_semen_id', $certificate->merek_semen_id) === $brand->id)>
                {{ $brand->nama_merek }} - {{ $brand->kategoriSemen?->nama_kategori }}
            </option>
        @endforeach
    </select>
</div>
