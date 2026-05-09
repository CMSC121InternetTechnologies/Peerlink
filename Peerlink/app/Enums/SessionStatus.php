<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle states for a TutoringSession row.
 *
 * String values match existing column data so we can cast the column directly:
 *   protected $casts = ['status' => SessionStatus::class];
 */
enum SessionStatus: string
{
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
