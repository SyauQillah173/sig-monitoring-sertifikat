<x-layouts::app :title="'Kategori Produk'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Master Data Admin"
            title="Kategori Produk"
            description="Kelola kelompok kategori yang digunakan untuk mengorganisasi produk."
        >
            <x-slot:actions>
                <a href="{{ route('admin.categories.create') }}" class="ui-button-primary">
                    Tambah Kategori
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
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <p class="ui-table-row-title">{{ $category->name }}</p>
                                    <p class="ui-table-row-meta">{{ $category->description ?: '-' }}</p>
                                </td>
                                <td>{{ $category->slug }}</td>
                                <td>
                                    <span class="{{ $category->is_active ? 'ui-badge ui-badge-active' : 'ui-badge ui-badge-neutral' }}">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>{{ $category->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="ui-button-secondary px-4 py-2 text-xs">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus kategori ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
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
                                    Belum ada kategori produk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination-shell">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</x-layouts::app>
