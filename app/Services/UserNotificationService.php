<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class UserNotificationService
{
    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginateForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $user->systemNotifications()
            ->with(['certificate.product'])
            ->visibleInInbox()
            ->latestFirst()
            ->paginate($perPage);
    }

    public function markAsReadForUser(Notification $notification, User $user): void
    {
        abort_unless($notification->user_id === $user->id, 403);

        $notification->markAsRead();
        $this->clearUnreadCountCache($user);
    }

    public function markAllAsReadForUser(User $user): int
    {
        $updated = $user->unreadSystemNotifications()
            ->update([
                'status' => NotificationStatus::Read->value,
                'read_at' => now(),
            ]);

        $this->clearUnreadCountCache($user);

        return $updated;
    }

    private function clearUnreadCountCache(User $user): void
    {
        Cache::forget("notifications.unread-count.{$user->id}");
    }
}
