<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use App\Services\AuditLogger;
use App\Services\SystemBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SystemBackupController extends Controller
{
    public function __construct(
        private readonly SystemBackupService $backups,
    ) {}

    public function index(): View
    {
        return view('admin.system-settings.backups.index', [
            'settings' => $this->backups->settings(),
            'health' => $this->backups->health(),
            'backups' => DatabaseBackup::query()
                ->with('user')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'backup_auto_enabled' => ['nullable', 'boolean'],
            'backup_include_private_files' => ['nullable', 'boolean'],
            'backup_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_max_count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->backups->saveSettings($payload);

        app(AuditLogger::class)->log('system_backup_settings_updated', null, 'Admin memperbarui pengaturan backup sistem.', null, $payload);

        return redirect()
            ->route('system-settings.backups.index')
            ->with('success', 'Pengaturan backup berhasil disimpan.');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $backup = $this->backups->create($request->user(), DatabaseBackup::TRIGGER_MANUAL);
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route('system-settings.backups.index')
                ->with('error', 'Backup gagal dibuat: '.$throwable->getMessage());
        }

        return redirect()
            ->route('system-settings.backups.index')
            ->with('success', 'Backup berhasil dibuat: '.$backup->filename);
    }

    public function cleanup(Request $request): RedirectResponse
    {
        $result = $this->backups->cleanup($request->user());

        return redirect()
            ->route('system-settings.backups.index')
            ->with('success', 'Cleanup selesai. '.$result['deleted_records'].' riwayat dan '.$result['deleted_files'].' file backup dibersihkan.');
    }

    public function download(DatabaseBackup $backup): StreamedResponse|RedirectResponse
    {
        if (! $backup->isDownloadable() || ! Storage::disk($backup->disk)->exists($backup->path)) {
            return redirect()
                ->route('system-settings.backups.index')
                ->with('error', 'File backup tidak ditemukan atau belum selesai dibuat.');
        }

        app(AuditLogger::class)->log('system_backup_downloaded', $backup, 'File backup sistem diunduh.', null, [
            'filename' => $backup->filename,
            'size' => $backup->size,
            'checksum' => $backup->checksum,
        ]);

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename);
    }

    public function destroy(DatabaseBackup $backup): RedirectResponse
    {
        if ($backup->path && Storage::disk($backup->disk)->exists($backup->path)) {
            Storage::disk($backup->disk)->delete($backup->path);
        }

        app(AuditLogger::class)->log('system_backup_deleted', $backup, 'Riwayat dan file backup sistem dihapus.', $backup->getOriginal(), null);
        $backup->delete();

        return redirect()
            ->route('system-settings.backups.index')
            ->with('success', 'Backup berhasil dihapus.');
    }
}
