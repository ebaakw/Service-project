<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return NotificationResource::collection($notifications)
            ->additional([
                'unread_count' => $user->unreadNotifications()->count(),
            ])
            ->response();
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'ok' => true,
            'notification' => new NotificationResource($notification->fresh()),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isCustomer(), 403);

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }
}
