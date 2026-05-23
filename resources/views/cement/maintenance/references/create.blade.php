<x-layouts::app :title="'Tambah '.$title">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Master Referensi" :title="'Tambah '.$title" description="Tambahkan pilihan baku baru." />
        <form method="POST" action="{{ route('cement.maintenance.references.store', $type) }}" class="ui-form-panel">
            @csrf
            @include('cement.maintenance.references._form', ['submitLabel' => 'Simpan Referensi'])
        </form>
    </div>
</x-layouts::app>
