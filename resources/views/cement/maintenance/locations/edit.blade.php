<x-layouts::app :title="'Edit Lokasi Pabrik'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Edit Lokasi Pabrik" description="Perbarui lokasi pabrik yang dipakai pada data sertifikat semen." />

        <form method="POST" action="{{ route('cement.maintenance.lokasi-pabrik.update', $location) }}" class="ui-form-panel">
            @method('PUT')
            @include('cement.maintenance.locations._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
