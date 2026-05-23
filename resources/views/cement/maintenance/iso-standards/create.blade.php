<x-layouts::app :title="'Tambah Master ISO'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Tambah Master ISO" description="Tambahkan standar ISO untuk sertifikat sistem semen." />

        <form method="POST" action="{{ route('cement.maintenance.iso-standards.store') }}" class="ui-form-panel">
            @csrf
            @include('cement.maintenance.iso-standards._form', ['submitLabel' => 'Simpan ISO'])
        </form>
    </div>
</x-layouts::app>
