<x-layouts::app :title="'Jenis Sertifikat'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Jenis Sertifikat"
            description="Kelola referensi jenis sertifikat yang dipakai oleh modul monitoring."
        >
            <x-slot:actions>
                <a href="{{ route('admin.certificate-types.create') }}" class="ui-button-primary">
                    Tambah Jenis Sertifikat
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-table-shell">
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificateTypes as $certificateType)
                            <tr>
                                <td>
                                    <p class="ui-table-row-title">{{ $certificateType->name }}</p>
                                    <p class="ui-table-row-meta">{{ $certificateType->description ?: '-' }}</p>
                                </td>
                                <td>{{ $certificateType->slug }}</td>
                                <td>{{ $certificateType->renewal_period_days ? $certificateType->renewal_period_days.' hari' : '-' }}</td>
                                <td>
                                    <span class="{{ $certificateType->is_active ? 'ui-badge ui-badge-active' : 'ui-badge ui-badge-neutral' }}">
                                        {{ $certificateType->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.certificate-types.edit', $certificateType) }}" class="ui-button-secondary px-4 py-2 text-xs">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.certificate-types.destroy', $certificateType) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus jenis sertifikat ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-button-danger px-4 py-2 text-xs">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                    Belum ada jenis sertifikat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-shell">
                {{ $certificateTypes->links() }}
            </div>
        </div>
    </div>
</x-layouts::app>
