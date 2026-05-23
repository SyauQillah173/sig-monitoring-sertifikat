@include('cement.maintenance.certificates.shared-errors')

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label class="ui-label" for="perusahaan_semen_id">Perusahaan</label>
        <select id="perusahaan_semen_id" name="perusahaan_semen_id" class="ui-select" required>
            <option value="">Pilih perusahaan</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('perusahaan_semen_id', $contact->perusahaan_semen_id) === $company->id)>
                    {{ $company->nama_perusahaan }}
                </option>
            @endforeach
        </select>
        <p class="ui-input-hint">
            Nama perusahaan diambil dari menu Perusahaan Semen.
            <a href="{{ route('cement.maintenance.perusahaan-semen.create') }}" class="ui-link">Tambah perusahaan</a>
            jika belum tersedia.
        </p>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="nama_pic">Nama PIC</label>
        <input id="nama_pic" name="nama_pic" value="{{ old('nama_pic', $contact->nama_pic) }}" class="ui-input" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="jabatan">Jabatan</label>
        <input id="jabatan" name="jabatan" value="{{ old('jabatan', $contact->jabatan) }}" class="ui-input">
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $contact->email) }}" class="ui-input" required>
    </div>

    <div class="space-y-2">
        <label class="ui-label" for="phone">Telepon</label>
        <input id="phone" name="phone" value="{{ old('phone', $contact->phone) }}" class="ui-input">
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="space-y-2">
            <label class="ui-label" for="is_primary">Kontak Utama</label>
            <select id="is_primary" name="is_primary" class="ui-select">
                <option value="0" @selected(! (bool) old('is_primary', $contact->is_primary))>Tidak</option>
                <option value="1" @selected((bool) old('is_primary', $contact->is_primary))>Ya</option>
            </select>
        </div>

        <div class="space-y-2">
            <label class="ui-label" for="is_active">Status</label>
            <select id="is_active" name="is_active" class="ui-select">
                <option value="1" @selected((bool) old('is_active', $contact->is_active))>Aktif</option>
                <option value="0" @selected(! (bool) old('is_active', $contact->is_active))>Nonaktif</option>
            </select>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button class="ui-button-primary">{{ $submitLabel }}</button>
</div>
