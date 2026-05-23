<x-layouts::app :title="'Edit Sertifikat SNI'">
    <div class="ui-page"><x-ui.page-header eyebrow="Pemeliharaan Data" title="Edit Sertifikat SNI" description="Perbarui data sertifikat SNI produk semen." /><form method="POST" action="{{ route('cement.maintenance.sertifikat-sni.update', $certificate) }}" enctype="multipart/form-data" class="ui-form-panel">@method('PUT')@include('cement.maintenance.certificates.sni._form', ['submitLabel' => 'Simpan Perubahan'])</form></div>
</x-layouts::app>
