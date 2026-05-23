<x-layouts::app :title="'Edit Sertifikat Sistem ISO'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Edit Sertifikat Sistem ISO" description="Perbarui sertifikat sistem manajemen ISO untuk pabrik/lokasi semen." />

        <form method="POST" action="{{ route('cement.maintenance.sertifikat-sistem.update', $certificate) }}" enctype="multipart/form-data" class="ui-form-panel">
            @csrf
            @method('PUT')
            @include('cement.maintenance.system-certificates._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
