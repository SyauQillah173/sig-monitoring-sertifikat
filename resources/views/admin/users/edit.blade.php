<x-layouts::app :title="'Edit User'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Admin" title="Edit User Login" description="{{ $user->email }}" />

        @include('admin.master-data.partials.flash-messages')

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="ui-form-panel">
            @method('PUT')
            @include('cement.maintenance.certificates.shared-errors')
            @include('admin.users._form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</x-layouts::app>
