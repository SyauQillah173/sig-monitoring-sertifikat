<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use App\Services\SystemBackupService;
use Illuminate\Console\Command;

class CreateSystemBackup extends Command
{
    protected $signature = 'system:backup {--force : Jalankan backup meski backup otomatis sedang nonaktif}';

    protected $description = 'Create scheduled system backup and cleanup old backups.';

    public function handle(SystemBackupService $backups): int
    {
        $settings = $backups->settings();

        if (($settings['backup_auto_enabled'] ?? '1') !== '1' && ! $this->option('force')) {
            $this->info('Backup otomatis sedang nonaktif. Gunakan --force untuk menjalankan manual dari CLI.');

            return self::SUCCESS;
        }

        $backup = $backups->create(null, DatabaseBackup::TRIGGER_SCHEDULED);
        $cleanup = $backups->cleanup();

        $this->info('Backup sistem berhasil dibuat.');
        $this->line('File: '.$backup->filename);
        $this->line('Ukuran: '.$backup->sizeForHumans());
        $this->line('Cleanup record: '.$cleanup['deleted_records']);
        $this->line('Cleanup file: '.$cleanup['deleted_files']);

        return self::SUCCESS;
    }
}
