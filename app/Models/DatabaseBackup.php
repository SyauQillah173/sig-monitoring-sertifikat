<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackup extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_SCHEDULED = 'scheduled';

    protected $fillable = [
        'user_id',
        'filename',
        'path',
        'disk',
        'status',
        'triggered_by',
        'format',
        'size',
        'checksum',
        'includes_private_files',
        'metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'includes_private_files' => 'boolean',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_SUCCESS && filled($this->path);
    }

    public function sizeForHumans(): string
    {
        $bytes = (int) ($this->size ?? 0);

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2).' MB';
        }

        return round($bytes / 1024 / 1024 / 1024, 2).' GB';
    }
}
