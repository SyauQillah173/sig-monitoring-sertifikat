<x-layouts::app :title="'Lembaga Penerbit'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Lembaga Penerbit"
            description="Kelola daftar lembaga atau instansi penerbit sertifikat."
        >
            <x-slot:actions>
                <a href="{{ route('admin.issuers.create') }}" class="ui-button-primary">
                    Tambah Lembaga
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <div class="ui-table-shell">
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Lembaga</th>
                            <th>Kontak</th>
                            <th>Website</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($issuers as $issuer)
                            <tr>
                                <td>
                                    <p class="ui-table-row-title">{{ $issuer->name }}</p>
                                    <p class="ui-table-row-meta">{{ $issuer->code ?: '-' }}</p>
                                </td>
                                <td>
                                    <p>{{ $issuer->contact_person ?: '-' }}</p>
                                    <p class="mt-1">{{ $issuer->email ?: ($issuer->phone ?: '-') }}</p>
                                </td>
                                <td>
                                    @if ($issuer->website)
                                        <a href="{{ $issuer->website }}" target="_blank" class="font-medium text-teal-700 hover:underline">{{ $issuer->website }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $issuer->is_active ? 'ui-badge ui-badge-active' : 'ui-badge ui-badge-neutral' }}">
                                        {{ $issuer->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.issuers.edit', $issuer) }}" class="ui-button-secondary px-4 py-2 text-xs">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.issuers.destroy', $issuer) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus lembaga penerbit ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
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
                                    Belum ada lembaga penerbit.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-shell">
                {{ $issuers->links() }}
            </div>
        </div>
    </div>
</x-layouts::app>
