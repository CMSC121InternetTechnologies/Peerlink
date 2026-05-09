<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Authorisation rules for TutoringSession lifecycle actions.
 *
 * Replaces the inline "Only the tutor can update the session status" check
 * that was in SessionController::update().
 */
class SessionPolicy
{
    /** Only the session's Tutor (per Session_Participants) can mark it complete or cancel it. */
    public function manage(User $user, TutoringSession $session): bool
    {
        return DB::table('Session_Participants')
            ->where('session_id', $session->session_id)
            ->where('user_id', $user->user_id)
            ->where('role', 'Tutor')
            ->exists();
    }
}
