<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SessionReview;
use App\Models\TutoringSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SessionController extends Controller
{
    // GET /api/sessions
    // Returns all sessions the current user participates in (as Tutor or Tutee).
    public function index(Request $request)
    {
        $user = $request->user();

        $sessionIds = DB::table('Session_Participants')
            ->where('user_id', $user->user_id)
            ->pluck('session_id');

        $sessions = TutoringSession::whereIn('session_id', $sessionIds)
            ->with(['request.course', 'request.student', 'request.tutor', 'room', 'participantUsers'])
            ->orderByDesc('scheduled_time')
            ->get();

        return response()->json([
            'sessions' => $sessions->map(fn($s) => $this->formatSession($s, $user->user_id)),
        ]);
    }

    // GET /api/sessions/open
    // Returns open group sessions (future, Scheduled) for students to browse and join.
    public function open(Request $request)
    {
        $user = $request->user();

        $joinedIds = DB::table('Session_Participants')
            ->where('user_id', $user->user_id)
            ->pluck('session_id');

        $sessions = TutoringSession::where('status', 'Scheduled')
            ->where('scheduled_time', '>', now())
            ->whereHas('request', fn($q) => $q->where('message', 'like', '[GROUP]%'))
            ->with(['request.course', 'request.tutor', 'room', 'participantUsers'])
            ->orderBy('scheduled_time')
            ->get();

        $roomCapacities = DB::table('Rooms')->pluck('capacity', 'room_id');

        return response()->json([
            'sessions' => $sessions->map(function ($s) use ($joinedIds, $roomCapacities) {
                $tuteeCount  = $s->participantUsers->where('pivot.role', 'Tutee')->count();
                $capacity    = $roomCapacities[$s->room_id] ?? 99;
                $alreadyJoined = $joinedIds->contains($s->session_id);

                return [
                    'session_id'    => $s->session_id,
                    'course'        => $s->request?->course?->course_code,
                    'courseName'    => $s->request?->course?->course_name,
                    'tutorName'     => $s->request?->tutor
                        ? ($s->request->tutor->first_name . ' ' . $s->request->tutor->last_name)
                        : 'Unknown',
                    'tutorId'       => $s->request?->tutor_id,
                    'message'       => ltrim(str_replace('[GROUP]', '', $s->request?->message ?? ''), ' '),
                    'scheduledTime' => $s->scheduled_time,
                    'modality'      => $s->modality,
                    'room'          => $s->room?->room_name,
                    'meetingLink'   => $s->meeting_link,
                    'tuteeCount'    => $tuteeCount,
                    'capacity'      => $capacity,
                    'full'          => $tuteeCount >= $capacity,
                    'alreadyJoined' => $alreadyJoined,
                ];
            }),
        ]);
    }

    // POST /api/sessions/{id}/join
    // Student joins an open group session.
    public function join(Request $request, string $id)
    {
        $user    = $request->user();
        $session = TutoringSession::findOrFail($id);

        if ($session->status !== 'Scheduled' || $session->scheduled_time <= now()) {
            return response()->json(['error' => 'This session is no longer open.'], 422);
        }

        // Must be a group session
        if (!str_starts_with($session->request?->message ?? '', '[GROUP]')) {
            return response()->json(['error' => 'This is not an open group session.'], 422);
        }

        // Already a participant?
        $already = DB::table('Session_Participants')
            ->where('session_id', $id)
            ->where('user_id', $user->user_id)
            ->exists();

        if ($already) {
            return response()->json(['error' => 'You have already joined this session.'], 422);
        }

        // Check capacity
        $tuteeCount = DB::table('Session_Participants')
            ->where('session_id', $id)
            ->where('role', 'Tutee')
            ->count();
        $capacity = DB::table('Rooms')->where('room_id', $session->room_id)->value('capacity') ?? 99;

        if ($tuteeCount >= $capacity) {
            return response()->json(['error' => 'This session is full.'], 422);
        }

        DB::table('Session_Participants')->insert([
            'participation_id' => (string) Str::uuid(),
            'session_id'       => $id,
            'user_id'          => $user->user_id,
            'role'             => 'Tutee',
            'has_attended'     => null,
            'joined_at'        => now(),
        ]);

        // Notify the tutor
        $tutorId = $session->request?->tutor_id;
        if ($tutorId && $tutorId !== $user->user_id) {
            $name = $user->first_name . ' ' . $user->last_name;
            Notification::create([
                'user_id'    => $tutorId,
                'type'       => 'student_joined',
                'message'    => "{$name} joined your group session for {$session->request?->course?->course_code}.",
                'request_id' => $session->request_id,
                'is_read'    => false,
            ]);
        }

        return response()->json(['message' => 'You have joined the session.']);
    }

    // PATCH /api/sessions/{id}
    // Actions: complete | cancel
    public function update(Request $request, string $id)
    {
        $user    = $request->user();
        $session = TutoringSession::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['complete', 'cancel'])],
        ]);

        // Only the tutor of the session may update its status
        $isTutor = DB::table('Session_Participants')
            ->where('session_id', $id)
            ->where('user_id', $user->user_id)
            ->where('role', 'Tutor')
            ->exists();

        if (!$isTutor) {
            return response()->json(['error' => 'Only the tutor can update the session status.'], 403);
        }

        if ($validated['action'] === 'complete') {
            if ($session->status !== 'Scheduled') {
                return response()->json(['error' => 'Only scheduled sessions can be completed.'], 422);
            }

            $session->status = 'Completed';
            $session->save();

            // Mark all participants as attended
            DB::table('Session_Participants')
                ->where('session_id', $id)
                ->update(['has_attended' => 1]);

            // Update the request status to Expired so it doesn't show in active lists
            if ($session->request) {
                $session->request->status = 'Expired';
                $session->request->save();
            }

            // Notify each tutee to leave a review
            $tutees = DB::table('Session_Participants')
                ->where('session_id', $id)
                ->where('role', 'Tutee')
                ->pluck('user_id');

            $courseCode = $session->request?->course?->course_code ?? 'the session';
            $tutorName  = $user->first_name . ' ' . $user->last_name;

            foreach ($tutees as $tuteeId) {
                $alreadyReviewed = SessionReview::where('session_id', $id)
                    ->where('reviewer_id', $tuteeId)
                    ->exists();

                if (!$alreadyReviewed) {
                    Notification::create([
                        'user_id'    => $tuteeId,
                        'type'       => 'session_completed',
                        'message'    => "Your session with {$tutorName} for {$courseCode} is complete. Leave a review!",
                        'request_id' => $session->request_id,
                        'is_read'    => false,
                    ]);
                }
            }

            return response()->json(['message' => 'Session marked as completed.']);
        }

        // cancel
        if ($session->status !== 'Scheduled') {
            return response()->json(['error' => 'Only scheduled sessions can be cancelled.'], 422);
        }

        $session->status = 'Cancelled';
        $session->save();

        // Notify all tutees
        $tutees = DB::table('Session_Participants')
            ->where('session_id', $id)
            ->where('role', 'Tutee')
            ->pluck('user_id');

        $courseCode = $session->request?->course?->course_code ?? 'a session';
        $tutorName  = $user->first_name . ' ' . $user->last_name;

        foreach ($tutees as $tuteeId) {
            Notification::create([
                'user_id'    => $tuteeId,
                'type'       => 'session_cancelled',
                'message'    => "Your session with {$tutorName} for {$courseCode} has been cancelled.",
                'request_id' => $session->request_id,
                'is_read'    => false,
            ]);
        }

        return response()->json(['message' => 'Session cancelled.']);
    }

    private function formatSession(TutoringSession $s, string $userId): array
    {
        $myRole = $s->participantUsers
            ->firstWhere('user_id', $userId)?->pivot->role ?? 'Tutee';

        $partner = $s->participantUsers->first(fn($u) => $u->user_id !== $userId);
        $partnerName = $partner
            ? ($partner->first_name . ' ' . $partner->last_name)
            : ($myRole === 'Tutor' ? 'Group Session' : 'Unknown');

        $isGroup = str_starts_with($s->request?->message ?? '', '[GROUP]');

        $hasReview = SessionReview::where('session_id', $s->session_id)
            ->where('reviewer_id', $userId)
            ->exists();

        $tuteeCount = $s->participantUsers->where('pivot.role', 'Tutee')->count();

        return [
            'session_id'    => $s->session_id,
            'request_id'    => $s->request_id,
            'course'        => $s->request?->course?->course_code,
            'courseName'    => $s->request?->course?->course_name,
            'partnerName'   => $partnerName,
            'partnerId'     => $partner?->user_id,
            'myRole'        => $myRole,
            'isGroup'       => $isGroup,
            'tuteeCount'    => $tuteeCount,
            'scheduledTime' => $s->scheduled_time,
            'modality'      => $s->modality,
            'room'          => $s->room?->room_name,
            'meetingLink'   => $s->meeting_link,
            'status'        => $s->status,
            'hasReview'     => $hasReview,
            'canReview'     => $s->status === 'Completed' && $myRole === 'Tutee' && !$hasReview,
            'tutorId'       => $s->request?->tutor_id,
        ];
    }
}
