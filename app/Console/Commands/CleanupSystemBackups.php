<?php

namespace App\Console\Commands;

use App\Services\SystemBackupService;
use Illuminate\Console\Command;

class CleanupSystemBackups extends Command
{
    protected $signature = 'system:backup-cleanup';

    protected $description = 'Delete old system backup files based on retention settings.';

    public function handle(SystemBackupService $backups): int
    {
        $result = $backups->cleanup();

        $this->info('Cleanup backup sistem selesai.');
        $this->line('Riwayat dihapus: '.$result['deleted_records']);
        $this->line('File dihapus: '.$result['deleted_files']);

        return self::SUCCESS;
    }
}
