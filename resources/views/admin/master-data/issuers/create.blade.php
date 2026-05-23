<x-layouts::app :title="'Tambah Lembaga Penerbit'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Tambah Lembaga Penerbit"
            description="Tambahkan lembaga penerbit baru untuk data referensi sertifikat."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('admin.issuers.store') }}">
                @include('admin.master-data.issuers._form', [
                    'submitLabel' => 'Simpan Lembaga',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
