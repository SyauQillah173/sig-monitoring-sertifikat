<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserNotificationService
{
    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginateForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $user->systemNotifications()
            ->with(['certificate.product'])
            ->latestFirst()
            ->paginate($perPage);
    }

    public function markAsReadForUser(Notification $notification, User $user): void
    {
        abort_unless($notification->user_id === $user->id, 403);

        $notification->markAsRead();
    }

    public function markAllAsReadForUser(User $user): int
    {
        return $user->unreadSystemNotifications()
            ->update([
                'status' => NotificationStatus::Read->value,
                'read_at' => now(),
            ]);
    }
}
