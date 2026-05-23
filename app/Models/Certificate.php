<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'product_id',
    'certificate_type_id',
    'issuer_id',
    'issued_by_user_id',
    'updated_by_user_id',
    'certificate_number',
    'title',
    'issued_at',
    'expires_at',
    'file_path',
    'status',
    'notes',
    'last_notified_at',
])]
class Certificate extends Model
{
    use HasFactory;

    private const EXPIRING_SOON_THRESHOLD_DAYS = 30;

    protected static function booted(): void
    {
        static::saving(function (Certificate $certificate): void {
            if ($certificate->expires_at && $certificate->usesMonitoringStatus()) {
                $certificate->status = $certificate->monitoringStatus();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'last_notified_at' => 'datetime',
            'status' => CertificateStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Issuer::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(CertificateRenewal::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeMonitoringActive(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '>', today()->addDays(self::EXPIRING_SOON_THRESHOLD_DAYS));
    }

    public function scopeMonitoringExpiringSoon(Builder $query): Builder
    {
        return $query
            ->whereDate('expires_at', '>=', today())
            ->whereDate('expires_at', '<=', today()->addDays(self::EXPIRING_SOON_THRESHOLD_DAYS));
    }

    public function scopeMonitoringExpired(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '<', today());
    }

    public function scopeFilterMonitoringStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            CertificateStatus::Active->value => $query->monitoringActive(),
            CertificateStatus::ExpiringSoon->value => $query->monitoringExpiringSoon(),
            CertificateStatus::Expired->value => $query->monitoringExpired(),
            default => $query,
        };
    }

    public function scopeUpcomingExpiry(Builder $query): Builder
    {
        return $query
            ->whereDate('expires_at', '>=', today())
            ->orderBy('expires_at')
            ->orderBy('certificate_number');
    }

    public function currentStatus(): CertificateStatus
    {
        $storedStatus = $this->storedStatus();

        return match ($storedStatus) {
            CertificateStatus::Draft,
            CertificateStatus::Revoked => $storedStatus,
            default => $this->monitoringStatus(),
        };
    }

    public function monitoringStatus(): CertificateStatus
    {
        return self::resolveStatusForExpiryDate($this->expires_at);
    }

    public function statusLabel(): string
    {
        return match ($this->currentStatus()) {
            CertificateStatus::Draft => 'Draft',
            CertificateStatus::Active => 'Aktif',
            CertificateStatus::ExpiringSoon => 'Akan Habis',
            CertificateStatus::Expired => 'Habis',
            CertificateStatus::Revoked => 'Dicabut',
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->currentStatus()) {
            CertificateStatus::Draft => 'ui-badge ui-badge-neutral',
            CertificateStatus::Active => 'ui-badge ui-badge-active',
            CertificateStatus::ExpiringSoon => 'ui-badge ui-badge-warning',
            CertificateStatus::Expired => 'ui-badge ui-badge-danger',
            CertificateStatus::Revoked => 'ui-badge ui-badge-info',
        };
    }

    public function hasDocument(): bool
    {
        return filled($this->file_path);
    }

    public function daysUntilExpiry(): int
    {
        return today()->diffInDays($this->expires_at, false);
    }

    public function expiryCountdownLabel(): string
    {
        $days = $this->daysUntilExpiry();

        return match (true) {
            $days < 0 => abs($days).' hari lewat',
            $days === 0 => 'Berakhir hari ini',
            $days === 1 => '1 hari lagi',
            default => $days.' hari lagi',
        };
    }

    public function downloadFilename(): string
    {
        $extension = pathinfo((string) $this->file_path, PATHINFO_EXTENSION) ?: 'pdf';
        $number = Str::slug($this->certificate_number, '-');

        return sprintf('sertifikat-%s.%s', $number ?: $this->getKey(), $extension);
    }

    public static function resolveStatusForExpiryDate(CarbonInterface|string $expiryDate): CertificateStatus
    {
        $expiryDate = $expiryDate instanceof CarbonInterface
            ? $expiryDate->toMutable()->startOfDay()
            : Carbon::parse($expiryDate)->startOfDay();

        $today = now()->startOfDay();

        if ($expiryDate->lt($today)) {
            return CertificateStatus::Expired;
        }

        if ($expiryDate->lte($today->copy()->addDays(self::EXPIRING_SOON_THRESHOLD_DAYS))) {
            return CertificateStatus::ExpiringSoon;
        }

        return CertificateStatus::Active;
    }

    /**
     * @return array<string, string>
     */
    public static function monitoringFilterOptions(): array
    {
        return [
            'all' => 'Semua',
            CertificateStatus::Active->value => 'Aktif',
            CertificateStatus::ExpiringSoon->value => 'Akan Habis',
            CertificateStatus::Expired->value => 'Habis',
        ];
    }

    /**
     * @return array{total: int, active: int, expiring_soon: int, expired: int}
     */
    public static function monitoringSummary(): array
    {
        $query = static::query();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->monitoringActive()->count(),
            'expiring_soon' => (clone $query)->monitoringExpiringSoon()->count(),
            'expired' => (clone $query)->monitoringExpired()->count(),
        ];
    }

    /**
     * @return array{active: int, expiring_soon: int, expired: int}
     */
    public static function monitoringAggregateCounts(): array
    {
        $today = today()->toDateString();
        $thresholdDate = today()->addDays(self::EXPIRING_SOON_THRESHOLD_DAYS)->toDateString();

        $aggregate = static::query()
            ->selectRaw(
                'SUM(CASE WHEN expires_at > ? THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN expires_at >= ? AND expires_at <= ? THEN 1 ELSE 0 END) as expiring_soon_count,
                SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired_count',
                [$thresholdDate, $today, $thresholdDate, $today],
            )
            ->first();

        return [
            'active' => (int) ($aggregate?->active_count ?? 0),
            'expiring_soon' => (int) ($aggregate?->expiring_soon_count ?? 0),
            'expired' => (int) ($aggregate?->expired_count ?? 0),
        ];
    }

    private function usesMonitoringStatus(): bool
    {
        $status = $this->storedStatus();

        return $status === null || in_array($status, [
            CertificateStatus::Active,
            CertificateStatus::ExpiringSoon,
            CertificateStatus::Expired,
        ], true);
    }

    private function storedStatus(): ?CertificateStatus
    {
        return $this->status instanceof CertificateStatus
            ? $this->status
            : CertificateStatus::tryFrom((string) $this->status);
    }
}
