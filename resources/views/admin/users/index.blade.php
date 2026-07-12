<x-layouts::app :title="'Manajemen User'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Admin" title="Manajemen User" description="Tambahkan user, atur role, status login, dan hak akses menu yang muncul setelah user login.">
            <x-slot:actions>
                <a href="{{ route('admin.users.create') }}" class="ui-button-primary">Tambah User</a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <form method="GET" class="ui-filter-bar">
            <input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari nama atau email user...">
            <button class="ui-button-secondary">Cari</button>
        </form>

        <div class="ui-table-shell">
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email Login</th>
                            <th>Role</th>
                            <th>Hak Akses</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $managedUser)
                            <tr>
                                <td>
                                    <p class="ui-table-row-title">{{ $managedUser->name }}</p>
                                    <p class="ui-table-row-meta">ID user #{{ $managedUser->id }}</p>
                                </td>
                                <td>{{ $managedUser->email }}</td>
                                <td><span class="ui-badge ui-badge-neutral">{{ $managedUser->roleLabel() }}</span></td>
                                <td>
                                    <span class="ui-badge {{ $managedUser->hasFullSystemAccess() ? 'ui-badge-success' : 'ui-badge-info' }}">
                                        {{ $managedUser->accessModeLabel() }}
                                    </span>
                                    @if ($managedUser->has_custom_access)
                                        <p class="ui-table-row-meta">{{ $managedUser->navigationItems->count() }} menu aktif</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="ui-badge {{ $managedUser->is_active ? 'ui-badge-success' : 'ui-badge-danger' }}">
                                        {{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.users.send-reset-link', $managedUser) }}">
                                            @csrf
                                            <button class="ui-button-secondary px-4 py-2 text-xs">Kirim Kode</button>
                                        </form>
                                        <a href="{{ route('admin.users.edit', $managedUser) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a>
                                        @if (! auth()->user()->is($managedUser))
                                            <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus user ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                                @csrf
                                                @method('DELETE')
                                                <button class="ui-button-danger px-4 py-2 text-xs">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-shell">{{ $users->links() }}</div>
        </div>
    </div>
</x-layouts::app>
