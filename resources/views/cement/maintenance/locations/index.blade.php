<x-layouts::app :title="'Data Lokasi Pabrik'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Data Lokasi Pabrik" description="Kelola lokasi pabrik yang dipakai pada sertifikat produk semen.">
            <x-slot:actions>
                <a href="{{ route('cement.maintenance.lokasi-pabrik.create') }}" class="ui-button-primary">Tambah Lokasi</a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <form method="GET" class="ui-filter-bar">
            <input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari lokasi, kode, alamat...">
            <button class="ui-button-secondary">Cari</button>
        </form>

        <div class="ui-table-shell">
            <div class="ui-table-wrap ui-maintenance-table-scroll">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>ID Master</th>
                            <th>Lokasi</th>
                            <th>Kode</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr>
                                <td><span class="ui-badge ui-badge-neutral">#{{ $location->id }}</span></td>
                                <td class="ui-table-row-title">{{ $location->nama_lokasi }}</td>
                                <td>{{ $location->kode ?: '-' }}</td>
                                <td>{{ $location->alamat ?: '-' }}</td>
                                <td>
                                    <span class="{{ $location->is_active ? 'ui-badge ui-badge-active' : 'ui-badge ui-badge-neutral' }}">
                                        {{ $location->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('cement.maintenance.lokasi-pabrik.edit', $location) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a>
                                        <form method="POST" action="{{ route('cement.maintenance.lokasi-pabrik.destroy', $location) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus lokasi pabrik ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ui-button-danger px-4 py-2 text-xs">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-400">Belum ada lokasi pabrik.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-shell">{{ $locations->links() }}</div>
        </div>
    </div>
</x-layouts::app>
