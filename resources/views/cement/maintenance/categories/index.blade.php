<x-layouts::app :title="'Data Kategori Semen'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Data Kategori Semen" description="Tambah, edit, hapus, dan cari kategori jenis semen.">
            <x-slot:actions><a href="{{ route('cement.maintenance.kategori-semen.create') }}" class="ui-button-primary">Tambah Kategori</a></x-slot:actions>
        </x-ui.page-header>
        @include('admin.master-data.partials.flash-messages')
        <form method="GET" class="ui-filter-bar"><input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari kategori semen..."><button class="ui-button-secondary">Cari</button></form>
        <div class="ui-table-shell"><div class="ui-table-wrap ui-maintenance-table-scroll"><table class="ui-table"><thead><tr><th>ID Master</th><th>Nama Kategori</th><th>Total Merek</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse ($categories as $category)
                <tr><td><span class="ui-badge ui-badge-neutral">#{{ $category->id }}</span></td><td class="ui-table-row-title">{{ $category->nama_kategori }}</td><td>{{ $category->merek_semen_count }}</td><td><div class="flex justify-end gap-2"><a href="{{ route('cement.maintenance.kategori-semen.edit', $category) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a><form method="POST" action="{{ route('cement.maintenance.kategori-semen.destroy', $category) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus kategori semen ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">@csrf @method('DELETE')<button class="ui-button-danger px-4 py-2 text-xs">Hapus</button></form></div></td></tr>
            @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">Belum ada kategori semen.</td></tr>
            @endforelse
        </tbody></table></div><div class="ui-pagination-shell">{{ $categories->links() }}</div></div>
    </div>
</x-layouts::app>
