<x-layouts::app :title="'Data Sertifikat Sistem ISO'">
    <div class="ui-page">
        <x-ui.page-header eyebrow="Pemeliharaan Data" title="Data Sertifikat Sistem ISO" description="Kelola sertifikat sistem manajemen ISO untuk pabrik/lokasi semen.">
            <x-slot:actions>
                <a href="{{ route('cement.maintenance.sertifikat-sistem.create') }}" class="ui-button-primary">Tambah Sertifikat Sistem</a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <form method="GET" class="ui-filter-bar">
            <input name="search" value="{{ request('search') }}" class="ui-input max-w-md" placeholder="Cari ISO, lokasi, nomor sertifikat, issuer...">
            <button class="ui-button-secondary">Cari</button>
        </form>

        <div class="ui-table-shell">
            <div class="ui-table-wrap ui-maintenance-table-scroll">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>ISO</th>
                            <th>Lokasi</th>
                            <th>Nomor</th>
                            <th>Korporasi</th>
                            <th>Tahap</th>
                            <th>Berlaku</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td><p class="ui-table-row-title">{{ $certificate->isoStandard?->code }}</p><p class="ui-table-row-meta">{{ $certificate->isoStandard?->name }}</p></td>
                                <td><p>{{ $certificate->lokasiPabrik?->nama_lokasi }}</p><p class="ui-table-row-meta">ID lokasi #{{ $certificate->lokasi_pabrik_id }}</p></td>
                                <td><p>{{ $certificate->certificate_number }}</p><p class="ui-table-row-meta">{{ $certificate->issuer ?: '-' }}</p></td>
                                <td><p>{{ $certificate->certificationLevelLabel() }}</p><p class="ui-table-row-meta">{{ $certificate->acquisition_year ?: '-' }}{{ $certificate->process_owner ? ' | '.$certificate->process_owner : '' }}</p></td>
                                <td><span class="{{ $certificate->auditStageBadgeClasses() }}">{{ $certificate->auditStageLabel() }}</span></td>
                                <td>{{ $certificate->issued_at->format('d M Y') }} s.d {{ $certificate->berlaku_sd->format('d M Y') }}</td>
                                <td><span class="{{ $certificate->statusBadgeClasses() }}">{{ $certificate->statusLabel() }}</span></td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('cement.maintenance.sertifikat-sistem.show', $certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">Detail</a>
                                        <a href="{{ route('cement.maintenance.sertifikat-sistem.edit', $certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">Edit</a>
                                        <form method="POST" action="{{ route('cement.maintenance.sertifikat-sistem.destroy', $certificate) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus sertifikat sistem ISO ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ui-button-danger px-4 py-2 text-xs">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Belum ada sertifikat sistem ISO.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="ui-pagination-shell">{{ $certificates->links() }}</div>
        </div>
    </div>
</x-layouts::app>
