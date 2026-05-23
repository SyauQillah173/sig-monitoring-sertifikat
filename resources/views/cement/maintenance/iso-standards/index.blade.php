<x-layouts::app :title="'Master ISO Sistem Semen'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Master ISO Sistem Semen" description="Kelola standar ISO yang dipakai untuk sertifikat sistem manajemen semen.">
            <x-slot:actions>
                <a href="{{ route('cement.maintenance.iso-standards.create') }}" class="ui-button-primary">Tambah ISO</a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <form method="GET" class="ui-filter-bar">
            <input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari ISO, nama sistem, atau catatan...">
            <button class="ui-button-secondary">Cari</button>
        </form>

        <div class="ui-table-shell">
            <div class="ui-table-wrap ui-maintenance-table-scroll">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>ID Master</th>
                            <th>Kode ISO</th>
                            <th>Nama Sistem</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($standards as $standard)
                            <tr>
                                <td><span class="ui-badge ui-badge-neutral">#{{ $standard->id }}</span></td>
                                <td><p class="ui-table-row-title">{{ $standard->code }}</p></td>
                                <td><p>{{ $standard->name }}</p><p class="ui-table-row-meta">{{ $standard->description ?: '-' }}</p></td>
                                <td>{{ $standard->sort_order }}</td>
                                <td><span class="ui-badge {{ $standard->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $standard->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('cement.maintenance.iso-standards.edit', $standard) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a>
                                        <form method="POST" action="{{ route('cement.maintenance.iso-standards.destroy', $standard) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus master ISO ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ui-button-danger px-4 py-2 text-xs">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada master ISO.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="ui-pagination-shell">{{ $standards->links() }}</div>
        </div>
    </div>
</x-layouts::app>
