<x-layouts::app :title="'Tambah Lokasi Pabrik'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Tambah Lokasi Pabrik" description="Tambahkan lokasi pabrik untuk dipakai pada data sertifikat semen." />

        <form method="POST" action="{{ route('cement.maintenance.lokasi-pabrik.store') }}" class="ui-form-panel">
            @include('cement.maintenance.locations._form', ['submitLabel' => 'Simpan Lokasi'])
        </form>
    </div>
</x-layouts::app>
