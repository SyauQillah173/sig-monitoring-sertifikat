<x-layouts::app :title="'Backup & Maintenance'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Pengaturan Sistem"
            title="Backup & Maintenance"
            description="Kelola backup database, file private sertifikat, cleanup otomatis, dan pemeriksaan kesehatan data."
        />

        @include('admin.master-data.partials.flash-messages')
        @include('cement.maintenance.certificates.shared-errors')

        <section class="ui-form-panel">
            <div class="grid gap-5 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="ui-label">Backup Sukses</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($health['total_success']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Riwayat backup berhasil.</p>
                </div>
                <div>
                    <p class="ui-label">Backup Gagal</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($health['total_failed']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Perlu dicek bila angkanya naik.</p>
                </div>
                <div>
                    <p class="ui-label">Ukuran Tersimpan</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">
                        {{ $health['total_size'] < 1024 * 1024 ? round($health['total_size'] / 1024, 2).' KB' : round($health['total_size'] / 1024 / 1024, 2).' MB' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Total file backup sukses.</p>
                </div>
                <div>
                    <p class="ui-label">Backup Terakhir</p>
                    <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">
                        {{ $health['latest']?->completed_at?->format('d M Y H:i') ?? 'Belum ada' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Jadwal otomatis berjalan 01:30.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="ui-form-panel p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="ui-title-sm">Riwayat Backup</h2>
                        <p class="ui-input-hint mt-1">Backup disimpan private di storage aplikasi. Hanya admin yang bisa membuat, mengunduh, dan menghapus.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('system-settings.backups.store') }}">
                            @csrf
                            <button class="ui-button-primary">Buat Backup Sekarang</button>
                        </form>
                        <form method="POST" action="{{ route('system-settings.backups.cleanup') }}">
                            @csrf
                            <button class="ui-button-secondary">Cleanup</button>
                        </form>
                    </div>
                </div>

                <div class="ui-table-shell mt-5">
                    <div class="ui-table-wrap">
                        <table class="ui-table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>File</th>
                                    <th>Status</th>
                                    <th>Mode</th>
                                    <th>Ukuran</th>
                                    <th>User</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($backups as $backup)
                                    <tr>
                                        <td>{{ $backup->created_at?->format('d M Y H:i') }}</td>
                                        <td>
                                            <div class="max-w-[260px] truncate font-semibold text-slate-900 dark:text-slate-100">{{ $backup->filename }}</div>
                                            @if ($backup->checksum)
                                                <div class="mt-1 max-w-[260px] truncate text-[11px] text-slate-500 dark:text-slate-400">SHA256: {{ $backup->checksum }}</div>
                                            @endif
                                            @if ($backup->error_message)
                                                <div class="mt-1 max-w-[260px] text-[11px] text-rose-600 dark:text-rose-300">{{ $backup->error_message }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span @class([
                                                'ui-badge',
                                                'ui-badge-active' => $backup->status === \App\Models\DatabaseBackup::STATUS_SUCCESS,
                                                'ui-badge-danger' => $backup->status === \App\Models\DatabaseBackup::STATUS_FAILED,
                                                'ui-badge-warning' => $backup->status === \App\Models\DatabaseBackup::STATUS_RUNNING,
                                            ])>{{ ucfirst($backup->status) }}</span>
                                        </td>
                                        <td>{{ ucfirst($backup->triggered_by) }}</td>
                                        <td>{{ $backup->sizeForHumans() }}</td>
                                        <td>{{ $backup->user?->name ?? 'Scheduler' }}</td>
                                        <td>
                                            <div class="flex justify-end gap-2">
                                                @if ($backup->isDownloadable())
                                                    <a class="ui-button-secondary px-3 py-2 text-xs" href="{{ route('system-settings.backups.download', $backup) }}">Download</a>
                                                @endif
                                                <form method="POST" action="{{ route('system-settings.backups.destroy', $backup) }}" data-confirm data-confirm-title="Konfirmasi Hapus" data-confirm-message="Hapus backup ini?" data-confirm-action="Hapus" data-confirm-loading-label="Menghapus...">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="ui-button-secondary px-3 py-2 text-xs">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-sm text-slate-500">Belum ada backup.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5">
                    {{ $backups->links() }}
                </div>
            </div>

            <aside class="space-y-6">
                <form method="POST" action="{{ route('system-settings.backups.update') }}" class="ui-form-panel p-6">
                    @csrf
                    @method('PUT')

                    <h2 class="ui-title-sm">Pengaturan Otomatis</h2>
                    <p class="ui-input-hint mt-1">Scheduler Laravel akan menjalankan backup otomatis setiap hari pukul 01:30 jika opsi aktif.</p>

                    <div class="mt-5 space-y-3">
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            <span>Backup otomatis</span>
                            <input type="hidden" name="backup_auto_enabled" value="0">
                            <input type="checkbox" name="backup_auto_enabled" value="1" @checked(old('backup_auto_enabled', $settings['backup_auto_enabled'] ?? '1') === '1')>
                        </label>
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            <span>Sertakan file private</span>
                            <input type="hidden" name="backup_include_private_files" value="0">
                            <input type="checkbox" name="backup_include_private_files" value="1" @checked(old('backup_include_private_files', $settings['backup_include_private_files'] ?? '1') === '1')>
                        </label>
                        <div class="space-y-2">
                            <label class="ui-label" for="backup_retention_days">Retensi Hari</label>
                            <input id="backup_retention_days" name="backup_retention_days" type="number" min="1" max="365" value="{{ old('backup_retention_days', $settings['backup_retention_days'] ?? '14') }}" class="ui-input">
                        </div>
                        <div class="space-y-2">
                            <label class="ui-label" for="backup_max_count">Maksimal Backup Disimpan</label>
                            <input id="backup_max_count" name="backup_max_count" type="number" min="1" max="100" value="{{ old('backup_max_count', $settings['backup_max_count'] ?? '10') }}" class="ui-input">
                        </div>
                    </div>

                    <button class="ui-button-primary mt-5 w-full">Simpan Pengaturan</button>
                </form>

                <section class="ui-form-panel p-6">
                    <h2 class="ui-title-sm">Health Check</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($health['warnings'] as $warning)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100">{{ $warning }}</div>
                        @empty
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-100">Tidak ada warning utama.</div>
                        @endforelse
                    </div>

                    <h3 class="ui-title-sm mt-6">Relasi Data</h3>
                    <div class="mt-3 space-y-2">
                        @foreach ($health['orphan_checks'] as $label => $count)
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                                <span>{{ str_replace('_', ' ', $label) }}</span>
                                <span>{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </aside>
        </section>
    </div>
</x-layouts::app>
