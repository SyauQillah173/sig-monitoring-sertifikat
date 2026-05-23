<div class="space-y-2">
    <label class="ui-label" for="lokasi_pabrik_id">Lokasi Pabrik</label>
    <select id="lokasi_pabrik_id" name="lokasi_pabrik_id" class="ui-select" required>
        <option value="">Pilih lokasi pabrik</option>
        @foreach ($locations as $location)
            <option value="{{ $location->id }}" @selected((int) old('lokasi_pabrik_id', $certificate->lokasi_pabrik_id) === $location->id)>
                ID #{{ $location->id }} - {{ $location->nama_lokasi }}{{ $location->kode ? ' - '.$location->kode : '' }}
            </option>
        @endforeach
    </select>
    <p class="ui-input-hint">Lokasi disimpan sebagai ID master dari tabel lokasi_pabrik.</p>
</div>
