<x-layouts::app :title="'Edit Merek Semen'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Edit Merek Semen" description="Perbarui merek semen dan kategorinya." />
        <form method="POST" action="{{ route('cement.maintenance.merek-semen.update', $brand) }}" class="ui-form-panel">
            @method('PUT')
            @include('cement.maintenance.brands._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
