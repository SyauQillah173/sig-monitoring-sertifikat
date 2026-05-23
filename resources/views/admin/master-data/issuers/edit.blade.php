<x-layouts::app :title="'Edit Lembaga Penerbit'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Edit Lembaga Penerbit"
            description="Perbarui data lembaga penerbit sesuai informasi terbaru."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('admin.issuers.update', $issuer) }}">
                @method('PUT')

                @include('admin.master-data.issuers._form', [
                    'submitLabel' => 'Perbarui Lembaga',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
