<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;

trait AuditsModelChanges
{
    protected static function bootAuditsModelChanges(): void
    {
        static::created(function ($model): void {
            app(AuditLogger::class)->log('created', $model, 'Data ditambahkan.', null, $model->getAttributes());
        });

        static::updated(function ($model): void {
            app(AuditLogger::class)->log('updated', $model, 'Data diperbarui.', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model): void {
            app(AuditLogger::class)->log('deleted', $model, 'Data dihapus.', $model->getOriginal(), null);
        });
    }
}
