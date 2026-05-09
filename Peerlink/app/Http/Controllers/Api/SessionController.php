<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SessionResource;
use App\Models\Room;
use App\Models\SessionParticipant;
use App\Models\SessionReview;
use App\Models\TutoringSession;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SessionController extends Controller
{
    use AuthorizesRequests;

    /** GET /api/sessions — all sessions where the user is a participant. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $sessionIds = SessionParticipant::where('user_id', $user->user_id)->pluck('session_id');

        $sessions = TutoringSession::whereIn('session_id', $sessionIds)
            ->with(['request.course', 'request.student', 'request.tutor', 'room', 'participantUsers'])
            ->orderByDesc('scheduled_time')
            ->get();

        // Pre-fetch reviewed-session IDs in one query so SessionResource never
        // fires a SessionReview::exists() inside the map loop (N+1 fix).
        $reviewedIds = SessionReview::whereIn('session_id', $sessions->pluck('session_id'))
            ->where('reviewer_id', $user->user_id)
            ->pluck('session_id')
            ->flip();

        return response()->json([
            'sessions' => $sessions->map(fn($s) => (new SessionResource($s))->additional([
                'perspective' => 'mine',
                'userId'      => $user->user_id,
                'reviewedIds' => $reviewedIds,
            ])),
        ]);
    }

    /** GET /api/sessions/open — future Scheduled group sessions students can join. */
    public function open(Request $request): JsonResponse
    {
        $user = $request->user();

        $joinedIds = SessionParticipant::where('user_id', $user->user_id)->pluck('session_id');

        $sessions = TutoringSession::where('status', SessionStatus::Scheduled->value)
            ->where('scheduled_time', '>', now())
            ->whereHas('request', fn($q) => $q->where('message', 'like', '[GROUP]%'))
            ->with(['request.course', 'request.tutor', 'room', 'participantUsers'])
            ->orderBy('scheduled_time')
            ->get();

        $roomCapacities = Room::pluck('capacity', 'room_id');

        return response()->json([
            'sessions' => $sessions->map(fn($s) => (new SessionResource($s))->additional([
                'perspective'    => 'open',
                'joinedIds'      => $joinedIds,
                'roomCapacities' => $roomCapacities,
            ])),
        ]);
    }

    /** POST /api/sessions/{id}/join — student joins a group session. */
    public function join(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $session = TutoringSession::findOrFail($id);

        if ($session->status !== SessionStatus::Scheduled || $session->scheduled_time <= now()) {
            return response()->json(['error' => 'This session is no longer open.'], 422);
        }

        if (!str_starts_with($session->request?->message ?? '', '[GROUP]')) {
            return response()->json(['error' => 'This is not an open group session.'], 422);
        }

        $already = SessionParticipant::where('session_id', $id)
            ->where('user_id', $user->user_id)
            ->exists();
        if ($already) {
            return response()->json(['error' => 'You have already joined this session.'], 422);
        }

        // Capacity check + insert wrapped in a transaction with a row-level
        // lock — without this, two concurrent joins could both pass the
        // capacity check and both insert, exceeding room capacity.
        $joined = false;
        DB::transaction(function () use ($id, $session, $user, &$joined): void {
            // SELECT … FOR UPDATE on the room row prevents two concurrent
            // joins from both passing the capacity check before either
            // inserts. Using the model keeps us consistent with the rest of
            // the file; the underlying SQL is identical to a raw DB::table().
            $capacity = Room::where('room_id', $session->room_id)
                ->lockForUpdate()
                ->value('capacity') ?? 99;

            $tuteeCount = SessionParticipant::where('session_id', $id)
                ->where('role', 'Tutee')
                ->count();

            if ($tuteeCount >= $capacity) {
                return; // $joined stays false → caller returns "session full"
            }

            SessionParticipant::create([
                'session_id'   => $id,
                'user_id'      => $user->user_id,
                'role'         => 'Tutee',
                'has_attended' => null,
                'joined_at'    => now(),
            ]);
            $joined = true;
        });

        if (!$joined) {
            return response()->json(['error' => 'This session is full.'], 422);
        }

        $tutorId = $session->request?->tutor_id;
        if ($tutorId && $tutorId !== $user->user_id) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            NotificationService::send(
                $tutorId,
                'student_joined',
                "{$name} joined your group session for {$session->request?->course?->course_code}.",
                $session->request_id,
            );
        }

        return response()->json(['message' => 'You have joined the session.']);
    }

    /** PATCH /api/sessions/{id} — actions: complete | cancel. */
    public function update(Request $request, string $id): JsonResponse
    {
        $session = TutoringSession::findOrFail($id);
        // SessionPolicy::manage checks the user is the session's Tutor (per
        // Session_Participants). Replaces the inline DB::table check.
        $this->authorize('manage', $session);

        $validated = $request->validate([
            'action'  => ['required', Rule::in(['complete', 'cancel'])],
            'summary' => ['nullable', 'string', 'max:500'],
        ]);

        return $validated['action'] === 'complete'
            ? $this->complete($request->user(), $session, $validated['summary'] ?? null)
            : $this->cancel($request->user(), $session);
    }

    private function complete($user, TutoringSession $session, ?string $summary): JsonResponse
    {
        if ($session->status !== SessionStatus::Scheduled) {
            return response()->json(['error' => 'Only scheduled sessions can be completed.'], 422);
        }
        if (now() < \Carbon\Carbon::parse($session->scheduled_time)) {
            return response()->json(['error' => 'Sessions can only be marked complete after their scheduled start time.'], 422);
        }

        $session->status  = SessionStatus::Completed->value;
        $session->summary = $summary;
        $session->save();

        SessionParticipant::where('session_id', $session->session_id)
            ->update(['has_attended' => 1]);

        // Mark request Expired so it falls out of active lists.
        if ($session->request) {
            $session->request->status = RequestStatus::Expired->value;
            $session->request->save();
        }

        $tutees = SessionParticipant::where('session_id', $session->session_id)
            ->where('role', 'Tutee')
            ->pluck('user_id');

        $courseCode = $session->request?->course?->course_code ?? 'the session';
        $tutorName  = trim($user->first_name . ' ' . $user->last_name);

        foreach ($tutees as $tuteeId) {
            $alreadyReviewed = SessionReview::where('session_id', $session->session_id)
                ->where('reviewer_id', $tuteeId)
                ->exists();
            if (!$alreadyReviewed) {
                NotificationService::send(
                    $tuteeId,
                    'session_completed',
                    "Your session with {$tutorName} for {$courseCode} is complete. Leave a review!",
                    $session->request_id,
                );
            }
        }

        return response()->json(['message' => 'Session marked as completed.']);
    }

    private function cancel($user, TutoringSession $session): JsonResponse
    {
        if ($session->status !== SessionStatus::Scheduled) {
            return response()->json(['error' => 'Only scheduled sessions can be cancelled.'], 422);
        }

        $session->status = SessionStatus::Cancelled->value;
        $session->save();

        $tutees = SessionParticipant::where('session_id', $session->session_id)
            ->where('role', 'Tutee')
            ->pluck('user_id');

        $courseCode = $session->request?->course?->course_code ?? 'a session';
        $tutorName  = trim($user->first_name . ' ' . $user->last_name);

        foreach ($tutees as $tuteeId) {
            NotificationService::send(
                $tuteeId,
                'session_cancelled',
                "Your session with {$tutorName} for {$courseCode} has been cancelled.",
                $session->request_id,
            );
        }

        return response()->json(['message' => 'Session cancelled.']);
    }

    // Session shaping moved to App\Http\Resources\SessionResource.
}
