<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle states for a TutoringRequest row.
 *
 * Replaces the magic strings ('Pending', 'Approved', …) that were scattered
 * across RequestController, the JS frontend, and notification messages.
 *
 * The string values match the existing column data verbatim so we can cast
 * the column without a data migration:
 *   protected $casts = ['status' => RequestStatus::class];
 */
enum RequestStatus: string
{
    case Pending         = 'Pending';
    case Approved        = 'Approved';
    case Declined        = 'Declined';
    case Expired         = 'Expired';
    case CounterProposed = 'CounterProposed';
    case Cancelled       = 'Cancelled';

    /** A tutor can still respond to (accept/decline/counter) requests in this state. */
    public function isActionableByTutor(): bool
    {
        return $this === self::Pending;
    }

    /** A student can still cancel or respond to a counter while in these states. */
    public function isActionableByStudent(): bool
    {
        return in_array($this, [self::Pending, self::CounterProposed], true);
    }
}
