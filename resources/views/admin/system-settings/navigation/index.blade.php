<x-layouts::app :title="'Menu Aplikasi'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Pengaturan Sistem"
            title="Menu Aplikasi"
            description="Atur label, icon, urutan, status, dan role yang boleh melihat menu sidebar."
        />

        @include('admin.master-data.partials.flash-messages')
        @include('cement.maintenance.certificates.shared-errors')

        <section class="ui-form-panel border-slate-200 bg-white/85 dark:border-slate-700 dark:bg-slate-900/80">
            <div class="grid gap-5 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Menu Aplikasi</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Dipakai untuk mengatur isi sidebar setelah user login. Perubahan disimpan ke database dan langsung dipakai saat halaman dibuka ulang.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Urutan & Grup</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Urutan menentukan posisi menu dari atas ke bawah. Grup adalah judul kelompok menu di sidebar, misalnya Platform, Monitoring, atau Laporan.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Label & Icon</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Label adalah nama menu yang tampil. Icon dipilih dari daftar resmi yang sudah disediakan sistem. Tujuan halaman dibuat fixed oleh sistem.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Role & Status</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Role menentukan siapa yang melihat menu. Status aktif menampilkan menu, status nonaktif menyembunyikannya tanpa menghapus data.</p>
                </div>
            </div>
        </section>

        @php($navigationGroupLabels = $items->pluck('group_label')->unique()->values())

        <form method="POST" action="{{ route('system-settings.navigation.update') }}" class="ui-table-shell" data-navigation-editor>
            @csrf
            @method('PUT')

            <section class="border-b border-slate-200 p-5 dark:border-slate-700">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Papan Seret Menu</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Seret kartu menu ke grup lain atau geser posisinya. Label, icon, role, dan status diatur langsung dari kartu menu.</p>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-normal text-slate-500 dark:text-slate-400">Klik Simpan Menu setelah selesai seret</p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-4">
                    @foreach ($navigationGroupLabels as $groupLabel)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-950/50">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <h3 class="text-xs font-semibold uppercase tracking-normal text-slate-700 dark:text-slate-200">{{ $groupLabel }}</h3>
                                <span class="rounded-full bg-white px-2 py-1 text-[0.68rem] font-semibold text-slate-500 shadow-sm dark:bg-slate-900 dark:text-slate-300" data-nav-count>{{ $items->where('group_label', $groupLabel)->count() }} menu</span>
                            </div>

                            <div class="flex min-h-24 flex-col gap-2 rounded-md border border-dashed border-slate-300 p-2 dark:border-slate-600" data-nav-lane data-group-label="{{ $groupLabel }}">
                                @foreach ($items->where('group_label', $groupLabel) as $item)
                                    @php($itemIndex = $items->search(fn ($candidate) => $candidate->id === $item->id))
                                    @php($selectedIcon = old('items.'.$itemIndex.'.icon', $item->icon))
                                    <div
                                        class="rounded-md border border-slate-200 bg-white p-3 text-sm text-slate-800 shadow-sm transition hover:border-sky-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-sky-500"
                                        data-nav-card
                                        data-nav-index="{{ $itemIndex }}"
                                    >
                                        <input type="hidden" name="items[{{ $itemIndex }}][id]" value="{{ $item->id }}">
                                        <input type="hidden" name="items[{{ $itemIndex }}][sort_order]" value="{{ old('items.'.$itemIndex.'.sort_order', $item->sort_order) }}" data-nav-field="sort_order" data-nav-index="{{ $itemIndex }}">
                                        <input type="hidden" name="items[{{ $itemIndex }}][group_label]" value="{{ old('items.'.$itemIndex.'.group_label', $item->group_label) }}" data-nav-field="group_label" data-nav-index="{{ $itemIndex }}">

                                        <div class="mb-3 flex items-center gap-3">
                                            <span class="flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md bg-slate-100 text-slate-600 active:cursor-grabbing dark:bg-slate-800 dark:text-slate-300" draggable="true" title="Seret menu">
                                                <flux:icon name="bars-3" variant="outline" class="size-4" />
                                            </span>
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300" data-nav-icon-preview>
                                                <flux:icon :name="$selectedIcon" variant="outline" class="size-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold" data-nav-card-label>{{ old('items.'.$itemIndex.'.label', $item->label) }}</span>
                                                <span class="block truncate text-xs font-normal text-slate-500 dark:text-slate-400">
                                                    Icon: <span data-nav-icon-label>{{ $icons[$selectedIcon] ?? $selectedIcon }}</span>
                                                </span>
                                            </span>
                                        </div>

                                        <div class="grid gap-3">
                                            <label class="grid gap-1">
                                                <span class="text-xs font-semibold uppercase tracking-normal text-slate-500 dark:text-slate-400">Label</span>
                                                <input name="items[{{ $itemIndex }}][label]" value="{{ old('items.'.$itemIndex.'.label', $item->label) }}" class="ui-input" data-nav-field="label" data-nav-index="{{ $itemIndex }}" required>
                                            </label>

                                            <label class="grid gap-1">
                                                <span class="text-xs font-semibold uppercase tracking-normal text-slate-500 dark:text-slate-400">Icon</span>
                                                <details class="rounded-md border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50">
                                                    <summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200">Pilih icon</summary>
                                                    <div class="grid max-h-64 gap-2 overflow-y-auto p-2">
                                                        @foreach ($icons as $iconName => $iconLabel)
                                                            <label class="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-sky-500 dark:hover:bg-slate-800">
                                                                <input
                                                                    type="radio"
                                                                    name="items[{{ $itemIndex }}][icon]"
                                                                    value="{{ $iconName }}"
                                                                    data-nav-icon-radio
                                                                    data-nav-icon-label-value="{{ $iconLabel }}"
                                                                    @checked($selectedIcon === $iconName)
                                                                    required
                                                                >
                                                                <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300" data-nav-icon-option>
                                                                    <flux:icon :name="$iconName" variant="outline" class="size-4" />
                                                                </span>
                                                                <span>{{ $iconLabel }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            </label>

                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-normal text-slate-500 dark:text-slate-400">Role</p>
                                                <div class="grid gap-2">
                                                    @foreach ($roles as $role)
                                                        <label class="inline-flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
                                                            <input
                                                                type="checkbox"
                                                                name="items[{{ $itemIndex }}][allowed_roles][]"
                                                                value="{{ $role->value }}"
                                                                @checked(in_array($role->value, old('items.'.$itemIndex.'.allowed_roles', $item->allowed_roles ?? []), true))
                                                            >
                                                            <span>{{ $role->label() }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="border-t border-slate-200 pt-3 dark:border-slate-700">
                                                <input type="hidden" name="items[{{ $itemIndex }}][is_active]" value="0">
                                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                    <input type="checkbox" name="items[{{ $itemIndex }}][is_active]" value="1" @checked(old('items.'.$itemIndex.'.is_active', $item->is_active))>
                                                    <span>Aktif di sidebar</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap gap-3 p-5">
                <button class="ui-button-primary">Simpan Menu</button>
                <a href="{{ route('system-settings.index') }}" class="ui-button-secondary">Kembali</a>
            </div>
        </form>
    </div>
</x-layouts::app>
