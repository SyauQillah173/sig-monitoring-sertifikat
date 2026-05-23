<x-layouts::app :title="'Tambah Sertifikat Produk'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Monitoring Sertifikat"
            title="Tambah Sertifikat Produk"
            description="Isi data sertifikat produk berikut dokumen scan jika sudah tersedia."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('certificates.store') }}" enctype="multipart/form-data">
                @include('certificates._form', [
                    'submitLabel' => 'Simpan Sertifikat',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
