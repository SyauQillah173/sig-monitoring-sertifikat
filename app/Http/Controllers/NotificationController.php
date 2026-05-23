<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\UserNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
    ) {}

    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $this->userNotificationService->paginateForUser($request->user()),
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): RedirectResponse
    {
        $this->userNotificationService->markAsReadForUser($notification, $request->user());

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->userNotificationService->markAllAsReadForUser($request->user());

        return back()->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }
}
