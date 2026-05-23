<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class SystemBackupService
{
    private const BACKUP_DIR = 'backups';

    private const TEMP_DIR = 'backups/tmp';

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        $defaults = $this->defaultSettings();

        if (! Schema::hasTable('system_settings')) {
            return $defaults;
        }

        $stored = SystemSetting::query()
            ->where('group', 'system_backup')
            ->pluck('value', 'key')
            ->all();

        return array_replace($defaults, $stored);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveSettings(array $values): void
    {
        foreach ($this->defaultSettings() as $key => $default) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) ($values[$key] ?? $default),
                    'group' => 'system_backup',
                    'label' => $this->labels()[$key] ?? $key,
                ],
            );
        }
    }

    public function create(?User $user = null, string $triggeredBy = DatabaseBackup::TRIGGER_MANUAL): DatabaseBackup
    {
        $settings = $this->settings();
        $includesPrivateFiles = ($settings['backup_include_private_files'] ?? '1') === '1';
        $startedAt = now();
        $baseName = 'sig-backup-'.$startedAt->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $temporaryDirectory = storage_path('app/private/'.self::TEMP_DIR.'/'.$baseName);

        File::ensureDirectoryExists($temporaryDirectory);
        File::ensureDirectoryExists(storage_path('app/private/'.self::BACKUP_DIR));

        $backup = DatabaseBackup::query()->create([
            'user_id' => $user?->id,
            'filename' => $baseName.'.zip',
            'disk' => 'local',
            'status' => DatabaseBackup::STATUS_RUNNING,
            'triggered_by' => $triggeredBy,
            'format' => 'zip',
            'includes_private_files' => $includesPrivateFiles,
            'started_at' => $startedAt,
        ]);

        try {
            $databaseDumpPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'database.sql';
            $this->dumpDatabase($databaseDumpPath);

            $metadata = $this->metadata($backup, $settings);
            File::put(
                $temporaryDirectory.DIRECTORY_SEPARATOR.'metadata.json',
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            );

            [$relativePath, $format] = $this->packBackup($baseName, $temporaryDirectory, $includesPrivateFiles);
            $absolutePath = Storage::disk('local')->path($relativePath);

            $backup->forceFill([
                'filename' => basename($relativePath),
                'path' => $relativePath,
                'format' => $format,
                'status' => DatabaseBackup::STATUS_SUCCESS,
                'size' => File::size($absolutePath),
                'checksum' => hash_file('sha256', $absolutePath),
                'metadata' => $metadata,
                'completed_at' => now(),
                'error_message' => null,
            ])->save();

            app(AuditLogger::class)->log('system_backup_created', $backup, 'Backup sistem berhasil dibuat.', null, [
                'triggered_by' => $triggeredBy,
                'filename' => $backup->filename,
                'size' => $backup->size,
                'checksum' => $backup->checksum,
            ]);
        } catch (Throwable $throwable) {
            $backup->forceFill([
                'status' => DatabaseBackup::STATUS_FAILED,
                'error_message' => Str::limit($throwable->getMessage(), 2000, ''),
                'completed_at' => now(),
            ])->save();

            app(AuditLogger::class)->log('system_backup_failed', $backup, 'Backup sistem gagal dibuat.', null, [
                'triggered_by' => $triggeredBy,
                'error' => $backup->error_message,
            ]);

            throw $throwable;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }

        return $backup->fresh();
    }

    /**
     * @return array{deleted_records: int, deleted_files: int}
     */
    public function cleanup(?User $user = null): array
    {
        $settings = $this->settings();
        $retentionDays = max(1, (int) ($settings['backup_retention_days'] ?? 14));
        $maxBackups = max(1, (int) ($settings['backup_max_count'] ?? 10));
        $cutoff = now()->subDays($retentionDays);
        $records = DatabaseBackup::query()
            ->orderByDesc('created_at')
            ->get();

        $successCounter = 0;
        $deletedRecords = 0;
        $deletedFiles = 0;

        foreach ($records as $record) {
            if ($record->status === DatabaseBackup::STATUS_SUCCESS) {
                $successCounter++;
            }

            $fileMissing = $record->path && ! Storage::disk($record->disk)->exists($record->path);
            $shouldDelete = $record->created_at?->lt($cutoff)
                || ($record->status === DatabaseBackup::STATUS_SUCCESS && $successCounter > $maxBackups)
                || $record->status === DatabaseBackup::STATUS_FAILED && $record->created_at?->lt(now()->subDays(3))
                || $record->status === DatabaseBackup::STATUS_SUCCESS && $fileMissing;

            if (! $shouldDelete) {
                continue;
            }

            if ($record->path && Storage::disk($record->disk)->exists($record->path)) {
                Storage::disk($record->disk)->delete($record->path);
                $deletedFiles++;
            }

            $record->delete();
            $deletedRecords++;
        }

        Storage::disk('local')->deleteDirectory(self::TEMP_DIR);

        app(AuditLogger::class)->log('system_backup_cleanup', null, 'Cleanup backup sistem dijalankan.', null, [
            'user_id' => $user?->id,
            'deleted_records' => $deletedRecords,
            'deleted_files' => $deletedFiles,
            'retention_days' => $retentionDays,
            'max_backups' => $maxBackups,
        ]);

        return [
            'deleted_records' => $deletedRecords,
            'deleted_files' => $deletedFiles,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $settings = $this->settings();
        $latest = DatabaseBackup::query()->where('status', DatabaseBackup::STATUS_SUCCESS)->latest('completed_at')->first();
        $warnings = [];

        if (($settings['backup_auto_enabled'] ?? '1') !== '1') {
            $warnings[] = 'Backup otomatis sedang nonaktif.';
        }

        if (! $latest) {
            $warnings[] = 'Belum ada backup sukses.';
        } elseif ($latest->completed_at?->lt(now()->subDay())) {
            $warnings[] = 'Backup sukses terakhir lebih dari 24 jam lalu.';
        }

        if (config('app.debug')) {
            $warnings[] = 'APP_DEBUG masih aktif. Matikan saat production.';
        }

        $db = config('database.connections.'.config('database.default'));
        if (($db['driver'] ?? null) === 'mysql' && ($db['username'] ?? '') === 'root') {
            $warnings[] = 'Database masih memakai user root. Buat user MySQL khusus aplikasi untuk production.';
        }

        if (($db['driver'] ?? null) === 'mysql' && blank($db['password'] ?? null)) {
            $warnings[] = 'Password database kosong. Wajib diberi password kuat saat production.';
        }

        if (($db['driver'] ?? null) === 'mysql' && ! $this->mysqldumpPath()) {
            $warnings[] = 'mysqldump belum ditemukan. Backup MySQL membutuhkan mysqldump.';
        }

        return [
            'latest' => $latest,
            'total_success' => DatabaseBackup::query()->where('status', DatabaseBackup::STATUS_SUCCESS)->count(),
            'total_failed' => DatabaseBackup::query()->where('status', DatabaseBackup::STATUS_FAILED)->count(),
            'total_size' => (int) DatabaseBackup::query()->where('status', DatabaseBackup::STATUS_SUCCESS)->sum('size'),
            'orphan_checks' => $this->orphanChecks(),
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function orphanChecks(): array
    {
        if (! Schema::hasTable('sertifikat_sistem_semen')) {
            return [];
        }

        return [
            'sni_missing_brand' => DB::table('sertifikat_sni')->leftJoin('merek_semen', 'sertifikat_sni.merek_semen_id', '=', 'merek_semen.id')->whereNull('merek_semen.id')->count(),
            'tkdn_missing_brand' => DB::table('sertifikat_tkdn')->leftJoin('merek_semen', 'sertifikat_tkdn.merek_semen_id', '=', 'merek_semen.id')->whereNull('merek_semen.id')->count(),
            'green_missing_brand' => DB::table('sertifikat_green_label')->leftJoin('merek_semen', 'sertifikat_green_label.merek_semen_id', '=', 'merek_semen.id')->whereNull('merek_semen.id')->count(),
            'system_missing_location' => DB::table('sertifikat_sistem_semen')->leftJoin('lokasi_pabrik', 'sertifikat_sistem_semen.lokasi_pabrik_id', '=', 'lokasi_pabrik.id')->whereNull('lokasi_pabrik.id')->count(),
            'system_missing_iso' => DB::table('sertifikat_sistem_semen')->leftJoin('iso_standards', 'sertifikat_sistem_semen.iso_standard_id', '=', 'iso_standards.id')->whereNull('iso_standards.id')->count(),
            'audit_events_missing_certificate' => DB::table('sertifikat_sistem_audit_events')->leftJoin('sertifikat_sistem_semen', 'sertifikat_sistem_audit_events.sertifikat_sistem_semen_id', '=', 'sertifikat_sistem_semen.id')->whereNull('sertifikat_sistem_semen.id')->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function defaultSettings(): array
    {
        return [
            'backup_auto_enabled' => '1',
            'backup_include_private_files' => '1',
            'backup_retention_days' => '14',
            'backup_max_count' => '10',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function labels(): array
    {
        return [
            'backup_auto_enabled' => 'Backup Otomatis',
            'backup_include_private_files' => 'Sertakan File Private',
            'backup_retention_days' => 'Retensi Backup',
            'backup_max_count' => 'Maksimal Backup Disimpan',
        ];
    }

    private function dumpDatabase(string $targetPath): void
    {
        $connection = config('database.default');
        $config = config('database.connections.'.$connection);

        match ($config['driver'] ?? null) {
            'mysql', 'mariadb' => $this->dumpMysql($config, $targetPath),
            'sqlite' => $this->dumpSqlite($targetPath),
            default => throw new RuntimeException('Driver database belum didukung untuk backup otomatis.'),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpMysql(array $config, string $targetPath): void
    {
        $mysqldump = $this->mysqldumpPath();

        if (! $mysqldump) {
            throw new RuntimeException('mysqldump tidak ditemukan. Atur BACKUP_MYSQLDUMP_PATH atau pastikan XAMPP MySQL tersedia.');
        }

        $arguments = [
            $mysqldump,
            '--host='.$config['host'],
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.$config['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            $config['database'],
        ];

        $environment = [];
        if (filled($config['password'] ?? null)) {
            $environment['MYSQL_PWD'] = $config['password'];
        }

        $process = new Process($arguments, base_path(), $environment, null, 180);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(Str::limit($process->getErrorOutput() ?: 'mysqldump gagal dijalankan.', 1000, ''));
        }

        File::put($targetPath, $process->getOutput());
    }

    private function dumpSqlite(string $targetPath): void
    {
        $connection = DB::connection();
        $tables = collect($connection->select("select name, sql from sqlite_master where type = 'table' and name not like 'sqlite_%'"))
            ->filter(fn (object $table) => filled($table->sql));

        $handle = fopen($targetPath, 'wb');

        if (! $handle) {
            throw new RuntimeException('File dump SQLite tidak bisa dibuat.');
        }

        fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n");

        foreach ($tables as $table) {
            fwrite($handle, 'DROP TABLE IF EXISTS '.$this->quoteIdentifier($table->name).";\n");
            fwrite($handle, $table->sql.";\n\n");

            $columns = collect($connection->select('PRAGMA table_info('.$this->quoteIdentifier($table->name).')'))
                ->pluck('name')
                ->values();

            DB::table($table->name)
                ->orderBy($columns->first() ?: 'rowid')
                ->get()
                ->each(function (object $row) use ($handle, $table, $columns): void {
                    $values = $columns
                        ->map(fn (string $column) => $this->valueToSql($row->{$column} ?? null))
                        ->implode(', ');

                    fwrite($handle, 'INSERT INTO '.$this->quoteIdentifier($table->name).' ('.$columns->map(fn (string $column) => $this->quoteIdentifier($column))->implode(', ').') VALUES ('.$values.");\n");
                });

            fwrite($handle, "\n");
        }

        fwrite($handle, "COMMIT;\nPRAGMA foreign_keys=ON;\n");
        fclose($handle);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function packBackup(string $baseName, string $temporaryDirectory, bool $includesPrivateFiles): array
    {
        if (! class_exists(ZipArchive::class)) {
            $relativePath = self::BACKUP_DIR.'/'.$baseName.'.sql';
            Storage::disk('local')->put($relativePath, File::get($temporaryDirectory.DIRECTORY_SEPARATOR.'database.sql'));

            return [$relativePath, 'sql'];
        }

        $relativePath = self::BACKUP_DIR.'/'.$baseName.'.zip';
        $absolutePath = Storage::disk('local')->path($relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));
        $zip = new ZipArchive;

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File ZIP backup tidak bisa dibuat.');
        }

        $zip->addFile($temporaryDirectory.DIRECTORY_SEPARATOR.'database.sql', 'database.sql');
        $zip->addFile($temporaryDirectory.DIRECTORY_SEPARATOR.'metadata.json', 'metadata.json');

        if ($includesPrivateFiles) {
            $this->addDirectoryToZip($zip, storage_path('app/private'), 'storage-private', [
                storage_path('app/private/'.self::BACKUP_DIR),
            ]);
            $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage-public');
        }

        $zip->close();

        return [$relativePath, 'zip'];
    }

    /**
     * @param  array<int, string>  $excludedDirectories
     */
    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix, array $excludedDirectories = []): void
    {
        if (! File::isDirectory($directory)) {
            return;
        }

        collect(File::allFiles($directory))
            ->filter(function ($file) use ($excludedDirectories): bool {
                $path = $file->getPathname();

                return collect($excludedDirectories)
                    ->every(fn (string $excludedDirectory) => ! str_starts_with($path, $excludedDirectory));
            })
            ->each(function ($file) use ($zip, $directory, $prefix): void {
                $relative = Str::of($file->getPathname())
                    ->after(rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
                    ->replace('\\', '/')
                    ->toString();

                $zip->addFile($file->getPathname(), $prefix.'/'.$relative);
            });
    }

    /**
     * @param  array<string, string>  $settings
     * @return array<string, mixed>
     */
    private function metadata(DatabaseBackup $backup, array $settings): array
    {
        return [
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'database_connection' => config('database.default'),
            'database_name' => config('database.connections.'.config('database.default').'.database'),
            'backup_id' => $backup->id,
            'triggered_by' => $backup->triggered_by,
            'started_at' => $backup->started_at?->toIso8601String(),
            'includes_private_files' => ($settings['backup_include_private_files'] ?? '1') === '1',
        ];
    }

    private function mysqldumpPath(): ?string
    {
        $candidates = collect([
            env('BACKUP_MYSQLDUMP_PATH'),
            'mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ])->filter();

        foreach ($candidates as $candidate) {
            if ($candidate !== 'mysqldump' && File::exists($candidate)) {
                return $candidate;
            }

            if ($candidate === 'mysqldump' && $this->commandExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function commandExists(string $command): bool
    {
        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';
        $process = Process::fromShellCommandline($finder.' '.$command, base_path(), null, null, 5);
        $process->run();

        return $process->isSuccessful();
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function valueToSql(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }
}
