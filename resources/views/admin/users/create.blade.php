<x-layouts::app :title="'Tambah User'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Admin" title="Tambah User Login" description="Daftarkan email Gmail yang boleh masuk ke aplikasi." />

        @include('admin.master-data.partials.flash-messages')

        <form method="POST" action="{{ route('admin.users.store') }}" class="ui-form-panel">
            @include('cement.maintenance.certificates.shared-errors')
            @include('admin.users._form', ['submitLabel' => 'Simpan User'])
        </form>
    </div>
</x-layouts::app>
