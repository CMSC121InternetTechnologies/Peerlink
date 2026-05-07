<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Room;
use App\Models\SessionReview;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    // GET /api/requests?role=student|tutor|broadcast
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $request->query('role', 'student');

        if ($role === 'tutor') {
            $requests = TutoringRequest::where('tutor_id', $user->user_id)
                ->where('student_id', '!=', $user->user_id)
                ->where('status', 'Pending')
                ->with(['student', 'course'])
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'requests' => $requests->map(fn($r) => [
                    'id'        => $r->request_id,
                    'tuteeName' => $r->student
                        ? ($r->student->first_name . ' ' . $r->student->last_name)
                        : 'Unknown',
                    'topic'     => ($r->course?->course_code ?? '') . ' — ' . ($r->course?->course_name ?? ''),
                    'course'    => $r->course?->course_code,
                    'date'      => $r->created_at,
                    'message'   => $r->message,
                    'status'    => $r->status,
                ]),
            ]);
        }

        if ($role === 'broadcast') {
            $requests = TutoringRequest::whereNull('tutor_id')
                ->where('status', 'Pending')
                ->where('student_id', '!=', $user->user_id)  // M7: hide own broadcasts
                ->with(['student', 'course'])
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'requests' => $requests->map(fn($r) => [
                    'id'          => $r->request_id,
                    'studentName' => $r->student
                        ? ($r->student->first_name . ' ' . $r->student->last_name)
                        : 'Unknown',
                    'course'      => $r->course?->course_code,
                    'topic'       => $r->course?->course_name ?? '',
                    'message'     => $r->message,
                    'date'        => $r->created_at,
                ]),
            ]);
        }

        // Student view: requests they sent — exclude self-referential group sessions
        $requests = TutoringRequest::where('student_id', $user->user_id)
            ->where(function ($q) use ($user) {
                $q->whereNull('tutor_id')
                  ->orWhere('tutor_id', '!=', $user->user_id);
            })
            ->where(function ($q) {
                // Exclude GROUP broadcasts where student == tutor (tutor-initiated group sessions)
                $q->whereNull('message')
                  ->orWhere('message', 'not like', '[GROUP]%');
            })
            ->with(['tutor', 'course', 'session.room'])
            ->orderByDesc('created_at')
            ->get();

        // Bug: Room::find() and SessionReview::exists() were called inside map(),
        // firing one extra query per request row (N+1). Fix: batch both lookups
        // before the loop using a single query each.
        $counterRoomIds = $requests
            ->where('status', 'CounterProposed')
            ->pluck('counter_proposed_room_id')
            ->filter()
            ->unique();
        $counterRooms = Room::whereIn('room_id', $counterRoomIds)->pluck('room_name', 'room_id');

        $sessionIds = $requests->pluck('session.session_id')->filter()->unique()->values();
        $reviewedSessionIds = SessionReview::whereIn('session_id', $sessionIds)
            ->where('reviewer_id', $user->user_id)
            ->pluck('session_id')
            ->flip(); // keyed by session_id for O(1) lookup

        return response()->json([
            'requests' => $requests->map(fn($r) => [
                'id'        => $r->request_id,
                'course'    => $r->course?->course_code,
                'tutorName' => $r->tutor
                    ? ($r->tutor->first_name . ' ' . $r->tutor->last_name)
                    : 'Broadcast',
                'tutorId'   => $r->tutor_id,
                'status'    => $r->status,
                'message'   => $r->message,
                'createdAt' => $r->created_at,
                'counterProposal' => $r->status === 'CounterProposed' ? [
                    'proposedTime' => $r->counter_proposed_time,
                    'message'      => $r->counter_proposed_message,
                    'modality'     => $r->counter_proposed_modality,
                    'room'         => $r->counter_proposed_room_id
                        ? ($counterRooms[$r->counter_proposed_room_id] ?? null)
                        : null,
                ] : null,
                'session' => $r->session ? [
                    'session_id'    => $r->session->session_id,
                    'modality'      => $r->session->modality,
                    'room'          => $r->session->room?->room_name,
                    'scheduledTime' => $r->session->scheduled_time,
                    'hasReview'     => isset($reviewedSessionIds[$r->session->session_id]),
                ] : null,
            ]),
        ]);
    }

    // POST /api/requests  (US_09 direct, US_12 broadcast when tutor_id omitted)
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_code'    => ['required', 'string', 'exists:Courses,course_code'],
            'tutor_id'       => ['nullable', 'string', 'exists:Users,user_id'],
            'message'        => ['nullable', 'string', 'max:1000'],
            // Bug: 'string' alone accepted arbitrary text that was then concatenated
            // directly into the message field. Fix: require a parseable date-time value
            // so the stored text is always well-formed and future-dated.
            'preferred_date' => ['nullable', 'date', 'after:now'],
        ]);

        $course  = Course::where('course_code', $validated['course_code'])->firstOrFail();

        // Prevent duplicate active requests
        $duplicate = TutoringRequest::where('student_id', $user->user_id)
            ->where('course_id', $course->course_id)
            ->whereIn('status', ['Pending', 'CounterProposed'])
            ->when(
                $validated['tutor_id'] ?? null,
                fn($q, $tid) => $q->where('tutor_id', $tid),
                fn($q)        => $q->whereNull('tutor_id')
            )
            ->exists();

        if ($duplicate) {
            return response()->json(['error' => 'You already have a pending request for this course.'], 422);
        }

        $message = $validated['message'] ?? '';
        if (!empty($validated['preferred_date'])) {
            $message = '[Preferred: ' . $validated['preferred_date'] . '] ' . $message;
        }

        $req = TutoringRequest::create([
            'student_id' => $user->user_id,
            'tutor_id'   => $validated['tutor_id'] ?? null,
            'course_id'  => $course->course_id,
            'message'    => $message ?: null,
            'status'     => 'Pending',
        ]);

        // Notify the tutor if this is a direct request
        if (!empty($validated['tutor_id'])) {
            $studentName = $user->first_name . ' ' . $user->last_name;
            Notification::create([
                'user_id'    => $validated['tutor_id'],
                'type'       => 'new_request',
                'message'    => "{$studentName} sent you a tutoring request for {$validated['course_code']}.",
                'request_id' => $req->request_id,
                'is_read'    => false,
            ]);
        }

        return response()->json(['message' => 'Request submitted.', 'request_id' => $req->request_id], 201);
    }

    // PATCH /api/requests/{id}
    // Tutor actions: accept | decline | claim | counter_propose
    // Student actions: student_accept | student_decline  (only when CounterProposed)
    public function respond(Request $request, string $id)
    {
        $user = $request->user();
        $req  = TutoringRequest::findOrFail($id);

        $validated = $request->validate([
            'action'               => ['required', Rule::in(['accept', 'decline', 'claim', 'counter_propose', 'student_accept', 'student_decline', 'cancel'])],
            'modality'             => ['nullable', Rule::in(['In-Person', 'Online'])],
            'room_id'              => ['nullable', 'integer', 'exists:Rooms,room_id'],
            'meeting_link'         => ['nullable', 'string', 'max:500'],
            'scheduled_time'       => ['nullable', 'string'],
            'counter_time'         => ['nullable', 'string'],
            'counter_message'      => ['nullable', 'string', 'max:1000'],
            'counter_modality'     => ['nullable', Rule::in(['In-Person', 'Online'])],
            'counter_room_id'      => ['nullable', 'integer', 'exists:Rooms,room_id'],
        ]);

        $action = $validated['action'];

        // --- Student responds to a counter-proposal ---
        if ($action === 'student_accept' || $action === 'student_decline') {
            if ($req->student_id !== $user->user_id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            if ($req->status !== 'CounterProposed') {
                return response()->json(['error' => 'No counter-proposal to respond to.'], 422);
            }

            if ($action === 'student_decline') {
                $req->status = 'Declined';
                $req->save();

                if ($req->tutor_id) {
                    $studentName = $user->first_name . ' ' . $user->last_name;
                    Notification::create([
                        'user_id'    => $req->tutor_id,
                        'type'       => 'counter_declined',
                        'message'    => "{$studentName} declined your counter-proposal for {$req->course?->course_code}.",
                        'request_id' => $req->request_id,
                        'is_read'    => false,
                    ]);
                }

                return response()->json(['message' => 'Counter-proposal declined.']);
            }

            // student_accept: create session from counter-proposed details
            $roomId = $req->counter_proposed_room_id
                ?? Room::where('room_type', 'Physical')->first()?->room_id
                ?? 1;

            try {
                DB::transaction(function () use ($req, $roomId, $user) {
                    $req->status = 'Approved';
                    $req->save();

                    $session = TutoringSession::create([
                        'request_id'     => $req->request_id,
                        'modality'       => $req->counter_proposed_modality ?? 'In-Person',
                        'room_id'        => $roomId,
                        'meeting_link'   => null,
                        'scheduled_time' => $req->counter_proposed_time
                            ? \Carbon\Carbon::parse($req->counter_proposed_time)->format('Y-m-d H:i:s')
                            : now()->addDays(3)->format('Y-m-d H:i:s'),
                        'status'         => 'Scheduled',
                    ]);
                    $this->addParticipants($session->session_id, $req->tutor_id, $req->student_id);
                });
            } catch (QueryException $e) {
                $isDuplicate = str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate');
                return response()->json([
                    'error' => $isDuplicate
                        ? 'A session already exists for this request.'
                        : 'Failed to create session. Please try again.',
                ], 422);
            }

            if ($req->tutor_id) {
                $studentName = $user->first_name . ' ' . $user->last_name;
                Notification::create([
                    'user_id'    => $req->tutor_id,
                    'type'       => 'counter_accepted',
                    'message'    => "{$studentName} accepted your counter-proposal for {$req->course?->course_code}.",
                    'request_id' => $req->request_id,
                    'is_read'    => false,
                ]);
            }

            return response()->json(['message' => 'Counter-proposal accepted and session scheduled.']);
        }

        if ($action === 'cancel') {
            if ($req->student_id !== $user->user_id)
                return response()->json(['error' => 'Unauthorized'], 403);
            if (!in_array($req->status, ['Pending', 'CounterProposed']))
                return response()->json(['error' => 'Only pending or counter-proposed requests can be cancelled.'], 422);

            $req->status = 'Cancelled';
            $req->save();

            if ($req->tutor_id) {
                $studentName = $user->first_name . ' ' . $user->last_name;
                Notification::create([
                    'user_id'    => $req->tutor_id,
                    'type'       => 'request_cancelled',
                    'message'    => "{$studentName} cancelled their tutoring request for {$req->course?->course_code}.",
                    'request_id' => $req->request_id,
                    'is_read'    => false,
                ]);
            }
            return response()->json(['message' => 'Request cancelled.']);
        }

        // --- Tutor-side actions ---
        $isBroadcast = $req->tutor_id === null;

        if ($isBroadcast) {
            // Bug: any authenticated user (including the request's own student) could
            // claim a broadcast because the ownership check was skipped entirely.
            // Fix: confirm the claimant has a TutorProfile and is not the original student.
            $isTutor = \App\Models\TutorProfile::where('user_id', $user->user_id)->exists();
            if (!$isTutor) {
                return response()->json(['error' => 'Only tutors can claim broadcast requests.'], 403);
            }
            if ($req->student_id === $user->user_id) {
                return response()->json(['error' => 'You cannot claim your own broadcast request.'], 403);
            }
        } elseif ($req->tutor_id !== $user->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Bug: no status check meant tutors could act on already-Approved, Declined,
        // or Expired requests, creating duplicate or inconsistent records.
        // Fix: reject any tutor-side action that is not targeting a Pending request.
        if ($req->status !== 'Pending') {
            return response()->json(['error' => 'This request is no longer pending.'], 422);
        }

        if ($action === 'decline') {
            $req->status = 'Declined';
            $req->save();

            $tutorName = $user->first_name . ' ' . $user->last_name;
            Notification::create([
                'user_id'    => $req->student_id,
                'type'       => 'request_declined',
                'message'    => "{$tutorName} declined your tutoring request for {$req->course?->course_code}.",
                'request_id' => $req->request_id,
                'is_read'    => false,
            ]);

            return response()->json(['message' => 'Request declined.']);
        }

        if ($action === 'counter_propose') {
            if (empty($validated['counter_time'])) {
                return response()->json(['error' => 'counter_time is required for a counter-proposal.'], 422);
            }

            $req->status                   = 'CounterProposed';
            $req->counter_proposed_time    = \Carbon\Carbon::parse($validated['counter_time'])->format('Y-m-d H:i:s');
            $req->counter_proposed_message = $validated['counter_message'] ?? null;
            $req->counter_proposed_modality = $validated['counter_modality'] ?? null;
            $req->counter_proposed_room_id  = $validated['counter_room_id'] ?? null;
            $req->save();

            $tutorName = $user->first_name . ' ' . $user->last_name;
            Notification::create([
                'user_id'    => $req->student_id,
                'type'       => 'counter_proposed',
                'message'    => "{$tutorName} proposed a new schedule for your {$req->course?->course_code} request.",
                'request_id' => $req->request_id,
                'is_read'    => false,
            ]);

            return response()->json(['message' => 'Counter-proposal sent.']);
        }

        // Accept or Claim: create session
        $roomId = $validated['room_id']
            ?? Room::where('room_type', 'Physical')->first()?->room_id
            ?? 1;

        $scheduledAt = $validated['scheduled_time']
            ? \Carbon\Carbon::parse($validated['scheduled_time'])->format('Y-m-d H:i:s')
            : now()->addDays(3)->format('Y-m-d H:i:s');

        try {
            DB::transaction(function () use ($req, $user, $isBroadcast, $validated, $roomId, $scheduledAt) {
                if ($isBroadcast) {
                    $req->tutor_id = $user->user_id;
                }
                $req->status = 'Approved';
                $req->save();

                $session = TutoringSession::create([
                    'request_id'     => $req->request_id,
                    'modality'       => $validated['modality'] ?? 'In-Person',
                    'room_id'        => $roomId,
                    'meeting_link'   => $validated['meeting_link'] ?? null,
                    'scheduled_time' => $scheduledAt,
                    'status'         => 'Scheduled',
                ]);
                $this->addParticipants($session->session_id, $user->user_id, $req->student_id);
            });
        } catch (QueryException $e) {
            $isDuplicate = str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate');
            return response()->json([
                'error' => $isDuplicate
                    ? 'A session already exists for this request.'
                    : 'Failed to create session. Please try again.',
            ], 422);
        }

        $tutorName = $user->first_name . ' ' . $user->last_name;
        Notification::create([
            'user_id'    => $req->student_id,
            'type'       => 'request_accepted',
            'message'    => "{$tutorName} accepted your tutoring request for {$req->course?->course_code}.",
            'request_id' => $req->request_id,
            'is_read'    => false,
        ]);

        return response()->json(['message' => 'Request accepted and session scheduled.']);
    }

    // POST /api/sessions/broadcast  (US_13 tutor posts group/open session)
    public function broadcastSession(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_code'    => ['required', 'string', 'exists:Courses,course_code'],
            'message'        => ['nullable', 'string', 'max:500'],
            'modality'       => ['required', Rule::in(['In-Person', 'Online'])],
            'room_id'        => ['nullable', 'integer', 'exists:Rooms,room_id'],
            'meeting_link'   => ['nullable', 'string', 'max:500'],
            'scheduled_time' => ['required', 'string'],
        ]);

        $course = Course::where('course_code', $validated['course_code'])->firstOrFail();
        $roomId = $validated['room_id']
            ?? Room::where('room_type', $validated['modality'] === 'Online' ? 'Virtual' : 'Physical')
                ->first()?->room_id
            ?? 1;

        // Bug: the three inserts (request, session, participant) were separate queries
        // with no transaction. A failure mid-way left the database in an inconsistent
        // state (e.g. an Approved request with no session). Fix: wrap all three in a
        // single atomic transaction so either all succeed or none do.
        $session = DB::transaction(function () use ($user, $course, $validated, $roomId) {
            $req = TutoringRequest::create([
                'student_id' => $user->user_id,
                'tutor_id'   => $user->user_id,
                'course_id'  => $course->course_id,
                'message'    => '[GROUP] ' . ($validated['message'] ?? ''),
                'status'     => 'Approved',
            ]);

            $session = TutoringSession::create([
                'request_id'     => $req->request_id,
                'modality'       => $validated['modality'],
                'room_id'        => $roomId,
                'meeting_link'   => $validated['meeting_link'] ?? null,
                'scheduled_time' => \Carbon\Carbon::parse($validated['scheduled_time'])->format('Y-m-d H:i:s'),
                'status'         => 'Scheduled',
            ]);

            // Tutor is the host; no student participant yet (students join later)
            DB::table('Session_Participants')->insert([
                'participation_id' => (string) Str::uuid(),
                'session_id'       => $session->session_id,
                'user_id'          => $user->user_id,
                'role'             => 'Tutor',
                'has_attended'     => null,
                'joined_at'        => now(),
            ]);

            return $session;
        });

        return response()->json(['message' => 'Group session posted.', 'session_id' => $session->session_id], 201);
    }

    private function addParticipants(string $sessionId, string $tutorId, string $studentId): void
    {
        $rows = [
            [
                'participation_id' => (string) Str::uuid(),
                'session_id'       => $sessionId,
                'user_id'          => $tutorId,
                'role'             => 'Tutor',
                'has_attended'     => null,
                'joined_at'        => now(),
            ],
        ];

        if ($studentId !== $tutorId) {
            $rows[] = [
                'participation_id' => (string) Str::uuid(),
                'session_id'       => $sessionId,
                'user_id'          => $studentId,
                'role'             => 'Tutee',
                'has_attended'     => null,
                'joined_at'        => now(),
            ];
        }

        DB::table('Session_Participants')->insert($rows);
    }
}
