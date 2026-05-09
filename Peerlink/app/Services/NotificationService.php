<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

/**
 * Single place to create user-facing notifications. Replaces 10+ scattered
 * `Notification::create([...])` blocks across RequestController and
 * SessionController.
 *
 * Why not Laravel's built-in $user->notify()? The legacy schema has a custom
 * Notifications table keyed by (user_id, type, message, request_id, is_read)
 * rather than the standard `notifications` morph table — using this thin
 * service is the smallest step toward consolidation without a schema rewrite.
 */
final class NotificationService
{
    public static function send(string $userId, string $type, string $message, ?string $requestId = null): void
    {
        // Don't notify yourself — the controllers all guarded against this
        // inline; centralising the check here means fewer if-blocks at
        // call sites.
        Notification::create([
            'user_id'    => $userId,
            'type'       => $type,
            'message'    => $message,
            'request_id' => $requestId,
            'is_read'    => false,
        ]);
    }
}
