<x-layouts::app :title="'Data Merek Semen'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Data Merek Semen" description="Kelola merek semen berdasarkan kategori.">
            <x-slot:actions><a href="{{ route('cement.maintenance.merek-semen.create') }}" class="ui-button-primary">Tambah Merek</a></x-slot:actions>
        </x-ui.page-header>
        @include('admin.master-data.partials.flash-messages')
        <form method="GET" class="ui-filter-bar"><input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari merek semen..."><select name="kategori_semen_id" class="ui-select max-w-sm"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) request('kategori_semen_id') === $category->id)>{{ $category->nama_kategori }}</option>@endforeach</select><button class="ui-button-secondary">Filter</button></form>
        <div class="ui-table-shell"><div class="ui-table-wrap ui-maintenance-table-scroll"><table class="ui-table"><thead><tr><th>ID Master</th><th>Merek</th><th>Kategori</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse ($brands as $brand)
                <tr><td><span class="ui-badge ui-badge-neutral">#{{ $brand->id }}</span></td><td class="ui-table-row-title">{{ $brand->nama_merek }}</td><td>{{ $brand->kategoriSemen?->nama_kategori }} <span class="ui-table-row-meta">ID kategori #{{ $brand->kategori_semen_id }}</span></td><td><div class="flex justify-end gap-2"><a href="{{ route('cement.maintenance.merek-semen.edit', $brand) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a><form method="POST" action="{{ route('cement.maintenance.merek-semen.destroy', $brand) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus merek semen ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">@csrf @method('DELETE')<button class="ui-button-danger px-4 py-2 text-xs">Hapus</button></form></div></td></tr>
            @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">Belum ada merek semen.</td></tr>
            @endforelse
        </tbody></table></div><div class="ui-pagination-shell">{{ $brands->links() }}</div></div>
    </div>
</x-layouts::app>
