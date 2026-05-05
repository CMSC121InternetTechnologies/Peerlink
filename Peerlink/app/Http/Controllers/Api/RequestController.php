<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Room;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
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
            // All pending broadcast requests (available for any tutor to claim)
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

        // Student view: requests they sent
        $requests = TutoringRequest::where('student_id', $user->user_id)
            ->where(function ($q) use ($user) {
                $q->whereNull('tutor_id')
                  ->orWhere('tutor_id', '!=', $user->user_id);
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
                'status'    => $r->status,
                'message'   => $r->message,
                'createdAt' => $r->created_at,
                'session'   => $r->session ? [
                    'modality'      => $r->session->modality,
                    'room'          => $r->session->room?->room_name,
                    'scheduledTime' => $r->session->scheduled_time,
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

        return response()->json(['message' => 'Request submitted.', 'request_id' => $req->request_id], 201);
    }

    // PATCH /api/requests/{id}  (US_11 accept/decline, US_12 broadcast claim, US_14 logistics)
    public function respond(Request $request, string $id)
    {
        $user = $request->user();
        $req  = TutoringRequest::findOrFail($id);

        // Allow tutor to respond if they are the assigned tutor, OR it's a broadcast (claim)
        $isBroadcast = $req->tutor_id === null;
        if (!$isBroadcast && $req->tutor_id !== $user->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'action'         => ['required', Rule::in(['accept', 'decline', 'claim'])],
            'modality'       => ['nullable', Rule::in(['In-Person', 'Online'])],
            'room_id'        => ['nullable', 'integer', 'exists:Rooms,room_id'],
            'meeting_link'   => ['nullable', 'string', 'max:500'],
            'scheduled_time' => ['nullable', 'string'],
        ]);

        if ($validated['action'] === 'decline') {
            $req->status = 'Declined';
            $req->save();
            return response()->json(['message' => 'Request declined.']);
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

        TutoringSession::create([
            'request_id'     => $req->request_id,
            'modality'       => $validated['modality'] ?? 'In-Person',
            'room_id'        => $roomId,
            'meeting_link'   => $validated['meeting_link'] ?? null,
            'scheduled_time' => $validated['scheduled_time'] ?? now()->addDays(3)->format('Y-m-d H:i:s'),
            'status'         => 'Scheduled',
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

        // Self-referential request used as a placeholder for tutor-initiated group sessions
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
