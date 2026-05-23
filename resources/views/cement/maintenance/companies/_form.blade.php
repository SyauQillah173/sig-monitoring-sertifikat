@include('cement.maintenance.certificates.shared-errors')
<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2"><label class="ui-label" for="nama_perusahaan">Nama Perusahaan</label><input id="nama_perusahaan" name="nama_perusahaan" value="{{ old('nama_perusahaan', $company->nama_perusahaan) }}" class="ui-input" required></div>
    <div class="space-y-2"><label class="ui-label" for="kode">Kode</label><input id="kode" name="kode" value="{{ old('kode', $company->kode) }}" class="ui-input"></div>
    <div class="space-y-2 md:col-span-2"><label class="ui-label" for="alamat">Alamat</label><textarea id="alamat" name="alamat" rows="4" class="ui-textarea">{{ old('alamat', $company->alamat) }}</textarea></div>
    <div class="space-y-2"><label class="ui-label" for="is_active">Status</label><select id="is_active" name="is_active" class="ui-select"><option value="1" @selected((bool) old('is_active', $company->is_active))>Aktif</option><option value="0" @selected(! (bool) old('is_active', $company->is_active))>Nonaktif</option></select></div>
</div>
<div class="mt-6 flex gap-3"><button class="ui-button-primary">{{ $submitLabel }}</button></div>
