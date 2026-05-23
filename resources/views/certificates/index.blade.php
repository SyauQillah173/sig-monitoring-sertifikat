<x-layouts::app :title="'Sertifikat Produk'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Monitoring Sertifikat"
            title="Sertifikat Produk"
            description="Kelola data sertifikat produk, masa berlaku, dan dokumen pendukung dalam satu modul monitoring yang rapi."
        >
            <x-slot:actions>
                <a href="{{ route('certificates.create') }}" class="ui-button-primary">
                    Tambah Sertifikat
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-filter-bar">
            @foreach ($statusFilters as $value => $label)
                <a
                    href="{{ $value === 'all' ? route('certificates.index') : route('certificates.index', ['status' => $value]) }}"
                    @class([
                        'ui-filter-chip',
                        'ui-filter-chip-active' => $selectedStatus === $value,
                    ])
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="ui-table-shell">
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Nomor Sertifikat</th>
                            <th>Produk</th>
                            <th>Jenis</th>
                            <th>Penerbit</th>
                            <th>Masa Berlaku</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td>
                                    <p class="ui-table-row-title">{{ $certificate->certificate_number }}</p>
                                    <p class="ui-table-row-meta">
                                        {{ $certificate->hasDocument() ? 'Dokumen tersedia' : 'Belum ada dokumen' }}
                                    </p>
                                </td>
                                <td>
                                    <p class="ui-table-row-title">{{ $certificate->product->name }}</p>
                                    <p class="ui-table-row-meta">{{ $certificate->product->category?->name ?? '-' }}</p>
                                </td>
                                <td>{{ $certificate->certificateType->name }}</td>
                                <td>{{ $certificate->issuer->name }}</td>
                                <td>
                                    <p>{{ $certificate->issued_at->format('d M Y') }}</p>
                                    <p class="mt-1">s.d. {{ $certificate->expires_at->format('d M Y') }}</p>
                                </td>
                                <td>
                                    <span class="{{ $certificate->statusBadgeClasses() }}">
                                        {{ $certificate->statusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('certificates.show', $certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">
                                            Detail
                                        </a>
                                        <a href="{{ route('certificates.edit', $certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('certificates.destroy', $certificate) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus data sertifikat ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
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
                                <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                                    Belum ada data sertifikat produk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-shell">
                {{ $certificates->links() }}
            </div>
        </div>
    </div>
</x-layouts::app>
