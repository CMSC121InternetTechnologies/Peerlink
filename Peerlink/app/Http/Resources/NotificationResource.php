<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a Notification row for the bell-icon dropdown.
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->notification_id,
            'type'       => $this->type,
            'message'    => $this->message,
            'request_id' => $this->request_id,
            'is_read'    => (bool) $this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}
