<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Actions accepted by PATCH /api/requests/{id}.
 *
 * Replaces the magic strings inside RequestController::respond()'s validate()
 * call and the chain of `if ($action === '…')` branches.
 */
enum RequestAction: string
{
    case Accept          = 'accept';           // tutor accepts a direct request
    case Decline         = 'decline';          // tutor declines a request
    case Claim           = 'claim';            // tutor claims a broadcast
    case CounterPropose  = 'counter_propose';  // tutor proposes alternate schedule
    case StudentAccept   = 'student_accept';   // student accepts the counter
    case StudentDecline  = 'student_decline';  // student declines the counter
    case Cancel          = 'cancel';           // student cancels their own request
}
