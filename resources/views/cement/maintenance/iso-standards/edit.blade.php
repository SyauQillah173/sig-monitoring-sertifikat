<x-layouts::app :title="'Edit Master ISO'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Edit Master ISO" description="Perbarui standar ISO sistem semen." />

        <form method="POST" action="{{ route('cement.maintenance.iso-standards.update', $standard) }}" class="ui-form-panel">
            @csrf
            @method('PUT')
            @include('cement.maintenance.iso-standards._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
