<x-layouts::app :title="'Edit Kategori Semen'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Edit Kategori Semen" description="Perbarui nama kategori semen." />
        <form method="POST" action="{{ route('cement.maintenance.kategori-semen.update', $category) }}" class="ui-form-panel">
            @method('PUT')
            @include('cement.maintenance.categories._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
