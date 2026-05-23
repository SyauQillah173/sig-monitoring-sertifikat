<x-layouts::app :title="'Perusahaan Semen'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Email Notifikasi" title="Perusahaan Semen" description="Kelola perusahaan penerima notifikasi sertifikat.">
            <x-slot:actions><a href="{{ route('cement.maintenance.perusahaan-semen.create') }}" class="ui-button-primary">Tambah Perusahaan</a></x-slot:actions>
        </x-ui.page-header>
        @include('admin.master-data.partials.flash-messages')
        <form method="GET" class="ui-filter-bar"><input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari perusahaan..."><button class="ui-button-secondary">Cari</button></form>
        <div class="ui-table-shell"><div class="ui-table-wrap ui-maintenance-table-scroll"><table class="ui-table"><thead><tr><th>Perusahaan</th><th>Kode</th><th>Kontak</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($companies as $company)<tr><td><p class="ui-table-row-title">{{ $company->nama_perusahaan }}</p><p class="ui-table-row-meta">{{ $company->alamat ?: '-' }}</p></td><td>{{ $company->kode ?: '-' }}</td><td>{{ $company->kontak_perusahaan_count }}</td><td><span class="ui-badge {{ $company->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $company->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="flex justify-end gap-2"><a href="{{ route('cement.maintenance.perusahaan-semen.edit', $company) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a><form method="POST" action="{{ route('cement.maintenance.perusahaan-semen.destroy', $company) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus perusahaan ini beserta kontaknya?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">@csrf @method('DELETE')<button class="ui-button-danger px-4 py-2 text-xs">Hapus</button></form></div></td></tr>@empty<tr><td colspan="5" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada perusahaan.</td></tr>@endforelse
        </tbody></table></div><div class="ui-pagination-shell">{{ $companies->links() }}</div></div>
    </div>
</x-layouts::app>
