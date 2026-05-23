<x-layouts::app :title="'Tambah Sertifikat SNI'">
    <div class="ui-page"><x-ui.page-header eyebrow="Pemeliharaan Data" title="Tambah Sertifikat SNI" description="Input data sertifikat SNI produk semen." /><form method="POST" action="{{ route('cement.maintenance.sertifikat-sni.store') }}" enctype="multipart/form-data" class="ui-form-panel">@include('cement.maintenance.certificates.sni._form', ['submitLabel' => 'Simpan Sertifikat SNI'])</form></div>
</x-layouts::app>
