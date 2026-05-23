<x-layouts::app :title="'Data Sertifikat SNI'">
    <div class="ui-page">
        
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Data Sertifikat SNI" description="Kelola sertifikat SNI produk semen.">
            <x-slot:actions><a href="{{ route('cement.maintenance.sertifikat-sni.create') }}" class="ui-button-primary">Tambah SNI</a></x-slot:actions>
        </x-ui.page-header>
        @include('admin.master-data.partials.flash-messages')
        <form method="GET" class="ui-filter-bar"><input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari SNI, merek, komoditi, LSPro..."><button class="ui-button-secondary">Cari</button></form>
        <div class="ui-table-shell"><div class="ui-table-wrap ui-maintenance-table-scroll"><table class="ui-table"><thead><tr><th>SNI</th><th>Merek</th><th>Komoditi</th><th>LSPro</th><th>Berlaku</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($certificates as $certificate)
                <tr><td><p>{{ $certificate->sni }}</p><p class="ui-table-row-meta">ID master #{{ $certificate->sni_reference_id ?: '-' }}</p></td><td><p class="ui-table-row-title">{{ $certificate->merekSemen?->nama_merek }}</p><p class="ui-table-row-meta">{{ $certificate->merekSemen?->kategoriSemen?->nama_kategori }} | ID merek #{{ $certificate->merek_semen_id }}</p></td><td><p>{{ $certificate->komoditi }}</p><p class="ui-table-row-meta">ID master #{{ $certificate->komoditi_reference_id ?: '-' }}</p></td><td><p>{{ $certificate->lspro }}</p><p class="ui-table-row-meta">ID master #{{ $certificate->lspro_reference_id ?: '-' }}</p></td><td>{{ $certificate->berlaku_sd->format('d M Y') }}</td><td><span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span></td><td><div class="flex justify-end gap-2"><a href="{{ route('cement.maintenance.sertifikat-sni.show', $certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">Detail</a><a href="{{ route('cement.maintenance.sertifikat-sni.edit', $certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a><form method="POST" action="{{ route('cement.maintenance.sertifikat-sni.destroy', $certificate) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus sertifikat SNI ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">@csrf @method('DELETE')<button class="ui-button-danger px-4 py-2 text-xs">Hapus</button></form></div></td></tr>
            @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">Belum ada sertifikat SNI.</td></tr>
            @endforelse
        </tbody></table></div><div class="ui-pagination-shell">{{ $certificates->links() }}</div></div>
    </div>
</x-layouts::app>
