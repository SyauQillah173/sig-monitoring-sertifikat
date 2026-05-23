<x-layouts::app :title="'Edit Kategori Produk'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Edit Kategori Produk"
            description="Perbarui informasi kategori produk sesuai kebutuhan sistem."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                @method('PUT')

                @include('admin.master-data.categories._form', [
                    'submitLabel' => 'Perbarui Kategori',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
