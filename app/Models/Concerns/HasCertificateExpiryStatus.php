<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait HasCertificateExpiryStatus
{
    private const EXPIRY_WARNING_DAYS = 90;

    public function statusKey(): string
    {
        $expiryDate = Carbon::parse($this->berlaku_sd)->startOfDay();
        $today = today();

        if ($expiryDate->lt($today)) {
            return 'kadaluarsa';
        }

        if ($expiryDate->lte($today->copy()->addDays(self::EXPIRY_WARNING_DAYS))) {
            return 'akan_berakhir';
        }

        return 'aktif';
    }

    public function statusLabel(): string
    {
        return match ($this->statusKey()) {
            'akan_berakhir' => 'Akan Berakhir',
            'kadaluarsa' => 'Kadaluarsa',
            default => 'Aktif',
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->statusKey()) {
            'akan_berakhir' => 'ui-badge ui-badge-warning',
            'kadaluarsa' => 'ui-badge ui-badge-danger',
            default => 'ui-badge ui-badge-active',
        };
    }

    public function scopeFilterExpiryStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'aktif' => $query->whereDate('berlaku_sd', '>', today()->addDays(self::EXPIRY_WARNING_DAYS)),
            'akan_berakhir' => $query
                ->whereDate('berlaku_sd', '>=', today())
                ->whereDate('berlaku_sd', '<=', today()->addDays(self::EXPIRY_WARNING_DAYS)),
            'kadaluarsa' => $query->whereDate('berlaku_sd', '<', today()),
            default => $query,
        };
    }

    public static function statusOptions(): array
    {
        return [
            'all' => 'Semua',
            'aktif' => 'Aktif',
            'akan_berakhir' => 'Akan Berakhir',
            'kadaluarsa' => 'Kadaluarsa',
        ];
    }
}
