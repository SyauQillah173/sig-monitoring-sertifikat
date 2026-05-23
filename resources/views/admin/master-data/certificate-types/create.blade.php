<x-layouts::app :title="'Tambah Jenis Sertifikat'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Tambah Jenis Sertifikat"
            description="Tambahkan referensi jenis sertifikat baru untuk modul sertifikat."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('admin.certificate-types.store') }}">
                @include('admin.master-data.certificate-types._form', [
                    'submitLabel' => 'Simpan Jenis Sertifikat',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
