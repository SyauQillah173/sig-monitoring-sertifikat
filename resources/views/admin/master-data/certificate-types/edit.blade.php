<x-layouts::app :title="'Edit Jenis Sertifikat'">
    <div class="ui-page-narrow">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Edit Jenis Sertifikat"
            description="Perbarui referensi jenis sertifikat sesuai kebutuhan organisasi."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-form-panel">
            <form method="POST" action="{{ route('admin.certificate-types.update', $certificateType) }}">
                @method('PUT')

                @include('admin.master-data.certificate-types._form', [
                    'submitLabel' => 'Perbarui Jenis Sertifikat',
                ])
            </form>
        </div>
    </div>
</x-layouts::app>
