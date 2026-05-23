<x-layouts::app :title="'Data '.$title">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Master Referensi" :title="'Data '.$title" description="Kelola Data Sertifikat">
            <x-slot:actions>
                <a href="{{ route('cement.maintenance.references.create', $type) }}" class="ui-button-primary">Tambah Referensi</a>
            </x-slot:actions>
        </x-ui.page-header>
        @include('admin.master-data.partials.flash-messages')
        <form method="GET" class="ui-filter-bar">
            <input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari referensi...">
            <button class="ui-button-secondary">Cari</button>
        </form>
        <div class="ui-table-shell">
            <div class="ui-table-wrap ui-maintenance-table-scroll">
                <table class="ui-table">
                    <thead><tr><th>ID Master</th><th>Nama</th><th>Kode</th><th>Status</th><th>Catatan</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($references as $reference)
                            <tr>
                                <td><span class="ui-badge ui-badge-neutral">#{{ $reference->id }}</span></td>
                                <td class="ui-table-row-title">{{ $reference->name }}</td>
                                <td>{{ $reference->code ?: '-' }}</td>
                                <td><span class="ui-badge {{ $reference->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $reference->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td>{{ $reference->description ?: '-' }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('cement.maintenance.references.edit', [$type, $reference]) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a>
                                        <form method="POST" action="{{ route('cement.maintenance.references.destroy', [$type, $reference]) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus referensi ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                            @csrf @method('DELETE')
                                            <button class="ui-button-danger px-4 py-2 text-xs">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada referensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="ui-pagination-shell">{{ $references->links() }}</div>
        </div>
    </div>
</x-layouts::app>
