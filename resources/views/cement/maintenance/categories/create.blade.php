<x-layouts::app :title="'Tambah Kategori Semen'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Tambah Kategori Semen" description="Masukkan kategori jenis semen baru." />
        <form method="POST" action="{{ route('cement.maintenance.kategori-semen.store') }}" class="ui-form-panel">
            @include('cement.maintenance.categories._form', ['submitLabel' => 'Simpan Kategori'])
        </form>
    </div>
</x-layouts::app>
