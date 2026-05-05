<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $notifications->map(fn($n) => [
                'id'         => $n->notification_id,
                'type'       => $n->type,
                'message'    => $n->message,
                'request_id' => $n->request_id,
                'is_read'    => (bool) $n->is_read,
                'created_at' => $n->created_at,
            ]),
        ]);
    }

    // PATCH /api/notifications/read  — mark all as read
    public function markAllRead(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Notifications marked as read.']);
    }
}
