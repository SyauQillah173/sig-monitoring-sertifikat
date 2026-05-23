<x-layouts::app :title="'Edit Sertifikat Produk'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Monitoring Sertifikat"
            title="Edit Sertifikat Produk"
            description="Perbarui data sertifikat, masa berlaku, atau ganti dokumen pendukung bila diperlukan."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('certificates.update', $certificate) }}" enctype="multipart/form-data">
                @method('PUT')

                @include('certificates._form', [
                    'submitLabel' => 'Perbarui Sertifikat',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
