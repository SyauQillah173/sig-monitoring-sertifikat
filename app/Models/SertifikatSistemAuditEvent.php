<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SertifikatSistemAuditEvent extends Model
{
    use AuditsModelChanges;

    public const TYPE_INITIAL = 'initial';

    public const TYPE_SURVEILEN_1 = 'surveilen_1';

    public const TYPE_SURVEILEN_2 = 'surveilen_2';

    public const TYPE_RENEWAL = 'renewal';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'sertifikat_sistem_semen_id',
        'user_id',
        'audit_type',
        'target_date',
        'completed_at',
        'status',
        'evidence_file',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(SertifikatSistemSemen::class, 'sertifikat_sistem_semen_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditTypeLabel(): string
    {
        return self::auditTypeOptions()[$this->audit_type] ?? Str::headline((string) $this->audit_type);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline((string) $this->status);
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'ui-badge ui-badge-active',
            self::STATUS_PENDING => 'ui-badge ui-badge-warning',
            default => 'ui-badge ui-badge-neutral',
        };
    }

    public function evidenceUrl(): ?string
    {
        if (blank($this->evidence_file)) {
            return null;
        }

        return route('cement.system-audit-evidence.download', $this);
    }

    /**
     * @return array<string, string>
     */
    public static function auditTypeOptions(): array
    {
        return [
            self::TYPE_INITIAL => 'Initial / Sertifikasi Awal',
            self::TYPE_SURVEILEN_1 => 'Surveilen 1',
            self::TYPE_SURVEILEN_2 => 'Surveilen 2',
            self::TYPE_RENEWAL => 'Renewal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_UPCOMING => 'Belum Waktunya',
            self::STATUS_PENDING => 'Perlu Tindak Lanjut',
            self::STATUS_COMPLETED => 'Selesai',
        ];
    }
}
