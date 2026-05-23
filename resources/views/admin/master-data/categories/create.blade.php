<x-layouts::app :title="'Tambah Kategori Produk'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Tambah Kategori Produk"
            description="Isi data kategori baru untuk kebutuhan pengelompokan produk."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @include('admin.master-data.categories._form', [
                    'submitLabel' => 'Simpan Kategori',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
