<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use App\Models\Concerns\HasCertificateExpiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SertifikatSistemSemen extends Model
{
    use AuditsModelChanges;
    use HasCertificateExpiryStatus;

    public const AUDIT_STAGE_SURVEILEN_1 = 'surveilen_1';

    public const AUDIT_STAGE_SURVEILEN_2 = 'surveilen_2';

    public const AUDIT_STAGE_RENEWAL = 'renewal';

    public const LEVEL_NASIONAL = 'nasional';

    public const LEVEL_INTERNASIONAL = 'internasional';

    protected $table = 'sertifikat_sistem_semen';

    protected $fillable = [
        'lokasi_pabrik_id',
        'iso_standard_id',
        'certificate_number',
        'issuer',
        'audit_stage',
        'scope',
        'issued_at',
        'berlaku_sd',
        'acquisition_year',
        'certification_level',
        'certification_category',
        'process_owner',
        'accreditation_number',
        'public_url',
        'description',
        'file_sertifikat',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'berlaku_sd' => 'date',
            'acquisition_year' => 'integer',
        ];
    }

    public function lokasiPabrik(): BelongsTo
    {
        return $this->belongsTo(LokasiPabrik::class);
    }

    public function isoStandard(): BelongsTo
    {
        return $this->belongsTo(IsoStandard::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(SertifikatSistemAuditEvent::class, 'sertifikat_sistem_semen_id')
            ->orderByRaw("case audit_type when 'initial' then 1 when 'surveilen_1' then 2 when 'surveilen_2' then 3 when 'renewal' then 4 else 99 end")
            ->orderBy('target_date');
    }

    public function certificationLevelLabel(): string
    {
        return self::certificationLevelOptions()[$this->certification_level] ?? '-';
    }

    public function auditStageLabel(): string
    {
        return self::auditStageOptions()[$this->audit_stage] ?? Str::headline((string) $this->audit_stage);
    }

    public function auditStageBadgeClasses(): string
    {
        return match ($this->audit_stage) {
            self::AUDIT_STAGE_SURVEILEN_1 => 'ui-badge ui-badge-info',
            self::AUDIT_STAGE_SURVEILEN_2 => 'ui-badge ui-badge-warning',
            self::AUDIT_STAGE_RENEWAL => 'ui-badge ui-badge-danger',
            default => 'ui-badge ui-badge-neutral',
        };
    }

    public function followUpAction(): string
    {
        return match ($this->audit_stage) {
            self::AUDIT_STAGE_SURVEILEN_1 => self::AUDIT_STAGE_SURVEILEN_1,
            self::AUDIT_STAGE_SURVEILEN_2 => self::AUDIT_STAGE_SURVEILEN_2,
            default => self::AUDIT_STAGE_RENEWAL,
        };
    }

    public function followUpActionLabel(): string
    {
        return match ($this->followUpAction()) {
            self::AUDIT_STAGE_SURVEILEN_1 => 'Ubah Status S1',
            self::AUDIT_STAGE_SURVEILEN_2 => 'Ubah Status S2',
            default => 'Input Data Baru',
        };
    }

    public function followUpTargetDate()
    {
        if (! $this->issued_at || ! $this->berlaku_sd) {
            return $this->berlaku_sd;
        }

        $targetDate = match ($this->followUpAction()) {
            self::AUDIT_STAGE_SURVEILEN_1 => $this->issued_at->copy()->addYear(),
            self::AUDIT_STAGE_SURVEILEN_2 => $this->issued_at->copy()->addYears(2),
            default => $this->berlaku_sd,
        };

        return $targetDate->gt($this->berlaku_sd) ? $this->berlaku_sd : $targetDate;
    }

    public function nextAuditStageAfterFollowUp(): string
    {
        return match ($this->followUpAction()) {
            self::AUDIT_STAGE_SURVEILEN_1 => self::AUDIT_STAGE_SURVEILEN_2,
            self::AUDIT_STAGE_SURVEILEN_2 => self::AUDIT_STAGE_RENEWAL,
            default => self::AUDIT_STAGE_SURVEILEN_1,
        };
    }

    public function certificateUrl(): ?string
    {
        if (blank($this->file_sertifikat)) {
            return null;
        }

        return route('cement.certificates.download', ['type' => 'system', 'certificate' => $this]);
    }

    public function validityProgress(): int
    {
        if (! $this->issued_at || ! $this->berlaku_sd) {
            return 0;
        }

        $totalDays = max(1, $this->issued_at->diffInDays($this->berlaku_sd));
        $elapsedDays = max(0, min($totalDays, $this->issued_at->diffInDays(today(), false)));

        return (int) round(($elapsedDays / $totalDays) * 100);
    }

    /**
     * @return array<string, string>
     */
    public static function auditStageOptions(): array
    {
        return [
            self::AUDIT_STAGE_SURVEILEN_1 => 'Surveilen 1',
            self::AUDIT_STAGE_SURVEILEN_2 => 'Surveilen 2',
            self::AUDIT_STAGE_RENEWAL => 'Renewal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function certificationLevelOptions(): array
    {
        return [
            self::LEVEL_NASIONAL => 'Nasional',
            self::LEVEL_INTERNASIONAL => 'Internasional',
        ];
    }
}
