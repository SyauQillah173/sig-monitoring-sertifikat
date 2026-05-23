<x-layouts::app :title="'Edit '.$title">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Master Referensi" :title="'Edit '.$title" description="Perbarui pilihan baku." />
        <form method="POST" action="{{ route('cement.maintenance.references.update', [$type, $reference]) }}" class="ui-form-panel">
            @csrf @method('PUT')
            @include('cement.maintenance.references._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
