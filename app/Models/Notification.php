<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'user_id',
    'certificate_id',
    'title',
    'message',
    'notification_type',
    'status',
    'scheduled_at',
    'sent_at',
    'read_at',
    'data',
])]
class Notification extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'data' => 'array',
            'status' => NotificationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::Unread);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest()->latest('scheduled_at');
    }

    public function scopeVisibleInInbox(Builder $query, int $readRetentionDays = 90): Builder
    {
        $cutoff = now()->subDays($readRetentionDays);

        return $query->where(function (Builder $query) use ($cutoff): void {
            $query->where('status', NotificationStatus::Unread->value)
                ->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->where('status', NotificationStatus::Read->value)
                        ->where(function (Builder $query) use ($cutoff): void {
                            $query->whereNull('read_at')
                                ->orWhere('read_at', '>=', $cutoff);
                        });
                })
                ->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->where('status', NotificationStatus::Dismissed->value)
                        ->where('updated_at', '>=', $cutoff);
                });
        });
    }

    public function markAsRead(): void
    {
        if ($this->status === NotificationStatus::Read) {
            return;
        }

        $this->forceFill([
            'status' => NotificationStatus::Read,
            'read_at' => $this->read_at ?? now(),
        ])->save();
    }

    public function markAsDismissed(): void
    {
        if ($this->status === NotificationStatus::Dismissed) {
            return;
        }

        $this->forceFill([
            'status' => NotificationStatus::Dismissed,
        ])->save();
    }
}
