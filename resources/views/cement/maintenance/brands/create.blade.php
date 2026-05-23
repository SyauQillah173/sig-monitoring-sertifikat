<x-layouts::app :title="'Tambah Merek Semen'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Tambah Merek Semen" description="Masukkan merek semen dan kategorinya." />
        <form method="POST" action="{{ route('cement.maintenance.merek-semen.store') }}" class="ui-form-panel">
            @include('cement.maintenance.brands._form', ['submitLabel' => 'Simpan Merek'])
        </form>
    </div>
</x-layouts::app>
