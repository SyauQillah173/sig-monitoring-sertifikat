<x-layouts::app :title="'Tambah Sertifikat Sistem ISO'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Tambah Sertifikat Sistem ISO" description="Input sertifikat sistem manajemen ISO untuk pabrik/lokasi semen." />

        <form method="POST" action="{{ route('cement.maintenance.sertifikat-sistem.store') }}" enctype="multipart/form-data" class="ui-form-panel">
            @csrf
            @include('cement.maintenance.system-certificates._form', ['submitLabel' => 'Simpan Sertifikat Sistem'])
        </form>
    </div>
</x-layouts::app>
