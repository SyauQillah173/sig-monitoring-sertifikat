<x-layouts::app :title="'Kontak Email Perusahaan'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Email Notifikasi" title="Kontak Email Perusahaan" description="Kelola PIC dan email penerima reminder sertifikat.">
            <x-slot:actions><a href="{{ route('cement.maintenance.kontak-perusahaan.create') }}" class="ui-button-primary">Tambah Kontak</a></x-slot:actions>
        </x-ui.page-header>
        @include('admin.master-data.partials.flash-messages')
        <form method="GET" class="ui-filter-bar"><input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari PIC, email, perusahaan..."><button class="ui-button-secondary">Cari</button></form>
        <div class="ui-table-shell"><div class="ui-table-wrap ui-maintenance-table-scroll"><table class="ui-table"><thead><tr><th>PIC</th><th>Perusahaan</th><th>Email</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($contacts as $contact)<tr><td><p class="ui-table-row-title">{{ $contact->nama_pic }}</p><p class="ui-table-row-meta">{{ $contact->jabatan ?: '-' }}</p></td><td>{{ $contact->perusahaanSemen?->nama_perusahaan }}</td><td>{{ $contact->email }}</td><td><span class="ui-badge {{ $contact->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $contact->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="flex justify-end gap-2"><a href="{{ route('cement.maintenance.kontak-perusahaan.edit', $contact) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a><form method="POST" action="{{ route('cement.maintenance.kontak-perusahaan.destroy', $contact) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus kontak ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">@csrf @method('DELETE')<button class="ui-button-danger px-4 py-2 text-xs">Hapus</button></form></div></td></tr>@empty<tr><td colspan="5" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada kontak.</td></tr>@endforelse
        </tbody></table></div><div class="ui-pagination-shell">{{ $contacts->links() }}</div></div>
    </div>
</x-layouts::app>
