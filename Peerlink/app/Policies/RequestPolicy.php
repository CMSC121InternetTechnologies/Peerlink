<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TutoringRequest;
use App\Models\TutorProfile;
use App\Models\User;

/**
 * Authorisation rules for TutoringRequest actions.
 *
 * Centralises the inline `if ($req->student_id !== $user->user_id) return 403`
 * checks that were sprinkled across RequestController::respond().
 *
 * Used in controllers via `$this->authorize('cancel', $req)` — Laravel will
 * automatically convert a `false` return value into a 403 JSON response when
 * the request asked for JSON.
 */
class RequestPolicy
{
    /** Tutor responds to a direct request (accept / decline / counter). */
    public function respond(User $user, TutoringRequest $req): bool
    {
        return $req->tutor_id === $user->user_id;
    }

    /**
     * Any authenticated user can claim an unassigned broadcast — except the
     * student who posted it. We deliberately do NOT require an existing
     * TutorProfile row: PeerLink users can pivot between tutee and tutor
     * roles freely, and the act of claiming creates the relationship. The
     * service layer (RequestActionService::claim) auto-creates a minimal
     * TutorProfile when needed so the FK on Tutor_Expertise still points
     * somewhere valid.
     *
     * Previously this strictly checked TutorProfile::exists(), which broke
     * the broadcast flow for new users who hadn't yet completed onboarding.
     */
    public function claim(User $user, TutoringRequest $req): bool
    {
        if ($req->tutor_id !== null) {
            return false; // not a broadcast
        }
        return $req->student_id !== $user->user_id;
    }

    /** Student cancels their own request. */
    public function cancel(User $user, TutoringRequest $req): bool
    {
        return $req->student_id === $user->user_id;
    }

    /** Student responds to a counter-proposal on their request. */
    public function respondToCounter(User $user, TutoringRequest $req): bool
    {
        return $req->student_id === $user->user_id;
    }
}
