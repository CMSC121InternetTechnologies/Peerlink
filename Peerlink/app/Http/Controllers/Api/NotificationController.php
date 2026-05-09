<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** GET /api/notifications */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'unread_count'  => $notifications->count(),
            'notifications' => NotificationResource::collection($notifications),
        ]);
    }

    /** PATCH /api/notifications/read — mark all unread as read. */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $updated = Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Notifications marked as read.', 'updated' => $updated]);
    }
}
