@csrf
<div class="grid gap-5 md:grid-cols-2">
    @include('cement.maintenance.certificates.shared-brand-field')
    @include('cement.maintenance.certificates.shared-reference-field', ['name' => 'sni_reference_id', 'label' => 'SNI', 'type' => \App\Models\CementReferenceValue::TYPE_SNI, 'value' => $certificate->sni_reference_id])
    @include('cement.maintenance.certificates.shared-reference-field', ['name' => 'komoditi_reference_id', 'label' => 'Komoditi', 'type' => \App\Models\CementReferenceValue::TYPE_KOMODITI, 'value' => $certificate->komoditi_reference_id])
    @include('cement.maintenance.certificates.shared-reference-field', ['name' => 'peringkat_green_label_reference_id', 'label' => 'Peringkat', 'type' => \App\Models\CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, 'value' => $certificate->peringkat_green_label_reference_id])
    @include('cement.maintenance.certificates.shared-location-field')
    <div class="space-y-2"><label class="ui-label" for="berlaku_sd">Berlaku s.d</label><input id="berlaku_sd" name="berlaku_sd" type="date" value="{{ old('berlaku_sd', optional($certificate->berlaku_sd)->format('Y-m-d')) }}" class="ui-input" required></div>
    <div class="space-y-2"><label class="ui-label" for="file_sertifikat">File Sertifikat</label><input id="file_sertifikat" name="file_sertifikat" type="file" class="ui-input" accept=".pdf,.jpg,.jpeg,.png"><p class="ui-table-row-meta">PDF/JPG/PNG maksimal 10 MB.</p></div>
</div>
@include('cement.maintenance.certificates.shared-errors')
<div class="mt-6 flex flex-wrap gap-3"><button class="ui-button-primary">{{ $submitLabel }}</button></div>
