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
                    'proposedTime'     => $r->counter_proposed_time,
                    'message'          => $r->counter_proposed_message,
                    'modality'         => $r->counter_proposed_modality,
                    'room'             => $r->counter_proposed_room_id
                        ? Room::find($r->counter_proposed_room_id)?->room_name
                        : null,
                ] : null,
                'session' => $r->session ? [
                    'session_id'    => $r->session->session_id,
                    'modality'      => $r->session->modality,
                    'room'          => $r->session->room?->room_name,
                    'scheduledTime' => $r->session->scheduled_time,
                    'hasReview'     => SessionReview::where('session_id', $r->session->session_id)
                        ->where('reviewer_id', $user->user_id)
                        ->exists(),
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
            'preferred_date' => ['nullable', 'string'],
        ]);

        $course  = Course::where('course_code', $validated['course_code'])->firstOrFail();
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
            'action'               => ['required', Rule::in(['accept', 'decline', 'claim', 'counter_propose', 'student_accept', 'student_decline'])],
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

            $req->status = 'Approved';
            $req->save();

            try {
                TutoringSession::create([
                    'request_id'     => $req->request_id,
                    'modality'       => $req->counter_proposed_modality ?? 'In-Person',
                    'room_id'        => $roomId,
                    'meeting_link'   => null,
                    'scheduled_time' => $req->counter_proposed_time ?? now()->addDays(3)->format('Y-m-d H:i:s'),
                    'status'         => 'Scheduled',
                ]);
            } catch (QueryException $e) {
                return response()->json(['error' => 'A session already exists for this request.'], 422);
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

        // --- Tutor-side actions ---
        $isBroadcast = $req->tutor_id === null;
        if (!$isBroadcast && $req->tutor_id !== $user->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
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
            $req->counter_proposed_time    = $validated['counter_time'];
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
        if ($isBroadcast) {
            $req->tutor_id = $user->user_id;
        }
        $req->status = 'Approved';
        $req->save();

        $roomId = $validated['room_id']
            ?? Room::where('room_type', 'Physical')->first()?->room_id
            ?? 1;

        try {
            TutoringSession::create([
                'request_id'     => $req->request_id,
                'modality'       => $validated['modality'] ?? 'In-Person',
                'room_id'        => $roomId,
                'meeting_link'   => $validated['meeting_link'] ?? null,
                'scheduled_time' => $validated['scheduled_time'] ?? now()->addDays(3)->format('Y-m-d H:i:s'),
                'status'         => 'Scheduled',
            ]);
        } catch (QueryException $e) {
            return response()->json(['error' => 'A session already exists for this request.'], 422);
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

        $req = TutoringRequest::create([
            'student_id' => $user->user_id,
            'tutor_id'   => $user->user_id,
            'course_id'  => $course->course_id,
            'message'    => '[GROUP] ' . ($validated['message'] ?? ''),
            'status'     => 'Approved',
        ]);

        TutoringSession::create([
            'request_id'     => $req->request_id,
            'modality'       => $validated['modality'],
            'room_id'        => $roomId,
            'meeting_link'   => $validated['meeting_link'] ?? null,
            'scheduled_time' => $validated['scheduled_time'],
            'status'         => 'Scheduled',
        ]);

        return response()->json(['message' => 'Group session posted.'], 201);
    }
}
