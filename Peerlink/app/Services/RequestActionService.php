<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\SessionStatus;
use App\Models\Room;
use App\Models\SessionParticipant;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use App\Models\TutorProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Houses the lifecycle logic for a TutoringRequest. Each public method
 * corresponds to one of the actions exposed by the API:
 *
 *   accept          → tutor schedules a session from a direct request
 *   claim           → tutor schedules a session from a broadcast request
 *   decline         → tutor rejects a request
 *   counterPropose  → tutor proposes a different time
 *   studentAccept   → student accepts the counter-proposal
 *   studentDecline  → student rejects the counter-proposal
 *   cancel          → student withdraws their own request
 *
 * Returns a [bool $ok, string $message] tuple. Auth checks happen in the
 * controller via Policies before this is called.
 *
 * Why a service class? Previously RequestController::respond() was 220
 * lines handling all 7 actions inline; pulling that into a single object
 * makes the behaviour testable in isolation and gives controllers a
 * narrow surface (parse → authorize → delegate → return).
 */
final class RequestActionService
{
    /**
     * @return array{0: bool, 1: string} [success, response message]
     */
    public function accept(User $user, TutoringRequest $req, array $opts): array
    {
        if ($req->status !== RequestStatus::Pending) {
            return [false, 'This request is no longer pending.'];
        }
        return $this->createSessionFromRequest($user, $req, $opts, isClaim: false);
    }

    public function claim(User $user, TutoringRequest $req, array $opts): array
    {
        if ($req->tutor_id !== null) {
            return [false, 'This is not a broadcast request.'];
        }
        if ($req->status !== RequestStatus::Pending) {
            return [false, 'This request is no longer pending.'];
        }

        // Make sure the claimer has a TutorProfile row before they pick up
        // their first broadcast. Tutor_Expertise has a FK on
        // tutor_profiles.user_id, and other parts of the app (the directory,
        // the rating average) read from this table. firstOrCreate is
        // idempotent so existing rows are untouched.
        TutorProfile::firstOrCreate(
            ['user_id' => $user->user_id],
            ['bio' => '', 'rating_avg' => 0],
        );

        return $this->createSessionFromRequest($user, $req, $opts, isClaim: true);
    }

    public function decline(User $user, TutoringRequest $req): array
    {
        if ($req->status !== RequestStatus::Pending) {
            return [false, 'This request is no longer pending.'];
        }

        $req->status = RequestStatus::Declined->value;
        $req->save();

        $tutorName = trim($user->first_name . ' ' . $user->last_name);
        NotificationService::send(
            $req->student_id,
            'request_declined',
            "{$tutorName} declined your tutoring request for {$req->course?->course_code}.",
            $req->request_id,
        );
        return [true, 'Request declined.'];
    }

    public function counterPropose(User $user, TutoringRequest $req, array $opts): array
    {
        if ($req->status !== RequestStatus::Pending) {
            return [false, 'This request is no longer pending.'];
        }
        if (empty($opts['counter_time'])) {
            return [false, 'counter_time is required for a counter-proposal.'];
        }

        $req->status                    = RequestStatus::CounterProposed->value;
        $req->counter_proposed_time     = Carbon::parse($opts['counter_time'])->format('Y-m-d H:i:s');
        $req->counter_proposed_message  = $opts['counter_message']  ?? null;
        $req->counter_proposed_modality = $opts['counter_modality'] ?? null;
        $req->counter_proposed_room_id  = $opts['counter_room_id']  ?? null;
        $req->save();

        $tutorName = trim($user->first_name . ' ' . $user->last_name);
        NotificationService::send(
            $req->student_id,
            'counter_proposed',
            "{$tutorName} proposed a new schedule for your {$req->course?->course_code} request.",
            $req->request_id,
        );
        return [true, 'Counter-proposal sent.'];
    }

    public function studentAccept(User $user, TutoringRequest $req): array
    {
        if ($req->status !== RequestStatus::CounterProposed) {
            return [false, 'No counter-proposal to respond to.'];
        }

        $roomId = $req->counter_proposed_room_id
            ?? Room::where('room_type', 'Physical')->first()?->room_id
            ?? 1;

        try {
            DB::transaction(function () use ($req, $roomId): void {
                $req->status = RequestStatus::Approved->value;
                $req->save();

                $session = TutoringSession::create([
                    'request_id'     => $req->request_id,
                    'modality'       => $req->counter_proposed_modality ?? 'In-Person',
                    'room_id'        => $roomId,
                    'meeting_link'   => null,
                    'scheduled_time' => $req->counter_proposed_time
                        ? Carbon::parse($req->counter_proposed_time)->format('Y-m-d H:i:s')
                        : now()->addDays(3)->format('Y-m-d H:i:s'),
                    'status'         => SessionStatus::Scheduled->value,
                ]);
                $this->addParticipants($session->session_id, $req->tutor_id, $req->student_id);
            });
        } catch (UniqueConstraintViolationException $e) {
            return [false, 'A session already exists for this request.'];
        }

        if ($req->tutor_id) {
            $studentName = trim($user->first_name . ' ' . $user->last_name);
            NotificationService::send(
                $req->tutor_id,
                'counter_accepted',
                "{$studentName} accepted your counter-proposal for {$req->course?->course_code}.",
                $req->request_id,
            );
        }
        return [true, 'Counter-proposal accepted and session scheduled.'];
    }

    public function studentDecline(User $user, TutoringRequest $req): array
    {
        if ($req->status !== RequestStatus::CounterProposed) {
            return [false, 'No counter-proposal to respond to.'];
        }

        $req->status = RequestStatus::Declined->value;
        $req->save();

        if ($req->tutor_id) {
            $studentName = trim($user->first_name . ' ' . $user->last_name);
            NotificationService::send(
                $req->tutor_id,
                'counter_declined',
                "{$studentName} declined your counter-proposal for {$req->course?->course_code}.",
                $req->request_id,
            );
        }
        return [true, 'Counter-proposal declined.'];
    }

    public function cancel(User $user, TutoringRequest $req): array
    {
        if (!in_array($req->status, [RequestStatus::Pending, RequestStatus::CounterProposed], true)) {
            return [false, 'Only pending or counter-proposed requests can be cancelled.'];
        }

        $req->status = RequestStatus::Cancelled->value;
        $req->save();

        if ($req->tutor_id) {
            $studentName = trim($user->first_name . ' ' . $user->last_name);
            NotificationService::send(
                $req->tutor_id,
                'request_cancelled',
                "{$studentName} cancelled their tutoring request for {$req->course?->course_code}.",
                $req->request_id,
            );
        }
        return [true, 'Request cancelled.'];
    }

    /**
     * Shared between accept() and claim(): flips the request to Approved,
     * creates the Session row, attaches participants. Wrapped in a
     * transaction so a half-completed write can't leave the DB inconsistent.
     */
    private function createSessionFromRequest(User $user, TutoringRequest $req, array $opts, bool $isClaim): array
    {
        $roomId = $opts['room_id']
            ?? Room::where('room_type', 'Physical')->first()?->room_id
            ?? 1;

        $scheduledAt = $opts['scheduled_time']
            ? Carbon::parse($opts['scheduled_time'])->format('Y-m-d H:i:s')
            : now()->addDays(3)->format('Y-m-d H:i:s');

        try {
            DB::transaction(function () use ($req, $user, $isClaim, $opts, $roomId, $scheduledAt): void {
                if ($isClaim) {
                    $req->tutor_id = $user->user_id;
                }
                $req->status = RequestStatus::Approved->value;
                $req->save();

                $session = TutoringSession::create([
                    'request_id'     => $req->request_id,
                    'modality'       => $opts['modality'] ?? 'In-Person',
                    'room_id'        => $roomId,
                    'meeting_link'   => $opts['meeting_link'] ?? null,
                    'scheduled_time' => $scheduledAt,
                    'status'         => SessionStatus::Scheduled->value,
                ]);
                $this->addParticipants($session->session_id, $user->user_id, $req->student_id);
            });
        } catch (UniqueConstraintViolationException $e) {
            return [false, 'A session already exists for this request.'];
        }

        $tutorName = trim($user->first_name . ' ' . $user->last_name);
        NotificationService::send(
            $req->student_id,
            'request_accepted',
            "{$tutorName} accepted your tutoring request for {$req->course?->course_code}.",
            $req->request_id,
        );

        return [true, $isClaim
            ? 'Broadcast claimed and session scheduled.'
            : 'Request accepted and session scheduled.'];
    }

    private function addParticipants(string $sessionId, string $tutorId, string $studentId): void
    {
        SessionParticipant::create([
            'session_id'   => $sessionId,
            'user_id'      => $tutorId,
            'role'         => 'Tutor',
            'has_attended' => null,
            'joined_at'    => now(),
        ]);

        if ($studentId !== $tutorId) {
            SessionParticipant::create([
                'session_id'   => $sessionId,
                'user_id'      => $studentId,
                'role'         => 'Tutee',
                'has_attended' => null,
                'joined_at'    => now(),
            ]);
        }
    }
}
