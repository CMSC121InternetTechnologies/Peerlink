<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTutoringRequestRequest;
use App\Http\Resources\RequestResource;
use App\Models\Course;
use App\Models\Room;
use App\Models\SessionParticipant;
use App\Models\SessionReview;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use App\Services\NotificationService;
use App\Services\RequestActionService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Slim controller: parses input, authorizes via Policies, delegates to
 * RequestActionService, returns a JSON response. The lifecycle logic
 * itself lives in RequestActionService — this class is now just an HTTP
 * adapter on top of the domain operations.
 */
class RequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private RequestActionService $actions)
    {
    }

    /** GET /api/requests?role=student|tutor|broadcast */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $request->query('role', 'student');

        if ($role === 'tutor') {
            $requests = TutoringRequest::where('tutor_id', $user->user_id)
                ->where('student_id', '!=', $user->user_id)
                ->where('status', RequestStatus::Pending->value)
                ->with(['student', 'course'])
                ->orderByDesc('created_at')
                ->get();

            return $this->collectionResponse($requests, ['perspective' => 'tutor']);
        }

        if ($role === 'broadcast') {
            $requests = TutoringRequest::whereNull('tutor_id')
                ->where('status', RequestStatus::Pending->value)
                ->where('student_id', '!=', $user->user_id)
                ->with(['student', 'course'])
                ->orderByDesc('created_at')
                ->get();

            return $this->collectionResponse($requests, ['perspective' => 'broadcast']);
        }

        // Student view: requests they sent — exclude self-referential group sessions.
        $requests = TutoringRequest::where('student_id', $user->user_id)
            ->where(fn($q) => $q->whereNull('tutor_id')->orWhere('tutor_id', '!=', $user->user_id))
            ->where(fn($q) => $q->whereNull('message')->orWhere('message', 'not like', '[GROUP]%'))
            ->with(['tutor', 'course', 'session.room'])
            ->orderByDesc('created_at')
            ->get();

        // Pre-fetch the per-row lookups so RequestResource can render without firing N+1 queries.
        $counterRoomIds = $requests
            ->where('status', RequestStatus::CounterProposed)
            ->pluck('counter_proposed_room_id')->filter()->unique();
        $counterRooms       = Room::whereIn('room_id', $counterRoomIds)->pluck('room_name', 'room_id');
        $sessionIds         = $requests->pluck('session.session_id')->filter()->unique()->values();
        $reviewedSessionIds = SessionReview::whereIn('session_id', $sessionIds)
            ->where('reviewer_id', $user->user_id)
            ->pluck('session_id')
            ->flip();

        return $this->collectionResponse($requests, [
            'perspective'        => 'student',
            'counterRooms'       => $counterRooms,
            'reviewedSessionIds' => $reviewedSessionIds,
        ]);
    }

    /** POST /api/requests — direct (US_09) or broadcast (US_12) when tutor_id omitted. */
    public function store(StoreTutoringRequestRequest $request): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validated();
        $course    = Course::where('course_code', $validated['course_code'])->firstOrFail();

        $duplicate = TutoringRequest::where('student_id', $user->user_id)
            ->where('course_id', $course->course_id)
            ->whereIn('status', [RequestStatus::Pending->value, RequestStatus::CounterProposed->value])
            ->when(
                $validated['tutor_id'] ?? null,
                fn($q, $tid) => $q->where('tutor_id', $tid),
                fn($q)        => $q->whereNull('tutor_id'),
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
            'status'     => RequestStatus::Pending->value,
        ]);

        if (!empty($validated['tutor_id'])) {
            $studentName = trim($user->first_name . ' ' . $user->last_name);
            NotificationService::send(
                $validated['tutor_id'],
                'new_request',
                "{$studentName} sent you a tutoring request for {$validated['course_code']}.",
                $req->request_id,
            );
        }

        return response()->json(['message' => 'Request submitted.', 'request_id' => $req->request_id], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Lifecycle endpoints — one URL per action, dispatched to the service.
    // Each authorize() call uses RequestPolicy: the controller no longer
    // contains inline `if ($req->student_id !== $user->user_id) return 403`.
    // ────────────────────────────────────────────────────────────────────────

    public function accept(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('respond', $req);

        $opts = $request->validate($this->scheduleRules());
        return $this->finish($this->actions->accept($request->user(), $req, $opts));
    }

    public function claim(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('claim', $req);

        $opts = $request->validate($this->scheduleRules());
        return $this->finish($this->actions->claim($request->user(), $req, $opts));
    }

    public function decline(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('respond', $req);
        return $this->finish($this->actions->decline($request->user(), $req));
    }

    public function counterPropose(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('respond', $req);

        $opts = $request->validate([
            'counter_time'     => ['required', 'string'],
            'counter_message'  => ['nullable', 'string', 'max:1000'],
            'counter_modality' => ['nullable', Rule::in(['In-Person', 'Online'])],
            'counter_room_id'  => ['nullable', 'integer', 'exists:Rooms,room_id'],
        ]);
        return $this->finish($this->actions->counterPropose($request->user(), $req, $opts));
    }

    public function studentAccept(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('respondToCounter', $req);
        return $this->finish($this->actions->studentAccept($request->user(), $req));
    }

    public function studentDecline(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('respondToCounter', $req);
        return $this->finish($this->actions->studentDecline($request->user(), $req));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $req = TutoringRequest::findOrFail($id);
        $this->authorize('cancel', $req);
        return $this->finish($this->actions->cancel($request->user(), $req));
    }

    /** POST /api/sessions/broadcast — tutor posts a group/open session (US_13). */
    public function broadcastSession(Request $request): JsonResponse
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

        $session = DB::transaction(function () use ($user, $course, $validated, $roomId) {
            $req = TutoringRequest::create([
                'student_id' => $user->user_id,
                'tutor_id'   => $user->user_id,
                'course_id'  => $course->course_id,
                'message'    => '[GROUP] ' . ($validated['message'] ?? ''),
                'status'     => RequestStatus::Approved->value,
            ]);

            $session = TutoringSession::create([
                'request_id'     => $req->request_id,
                'modality'       => $validated['modality'],
                'room_id'        => $roomId,
                'meeting_link'   => $validated['meeting_link'] ?? null,
                'scheduled_time' => Carbon::parse($validated['scheduled_time'])->format('Y-m-d H:i:s'),
                'status'         => SessionStatus::Scheduled->value,
            ]);

            SessionParticipant::create([
                'session_id'   => $session->session_id,
                'user_id'      => $user->user_id,
                'role'         => 'Tutor',
                'has_attended' => null,
                'joined_at'    => now(),
            ]);

            return $session;
        });

        return response()->json([
            'message'    => 'Group session posted.',
            'session_id' => $session->session_id,
        ], 201);
    }

    // ── private helpers ────────────────────────────────────────────────────

    /** Validation rules shared by accept/claim — both schedule a session. */
    private function scheduleRules(): array
    {
        return [
            'modality'       => ['nullable', Rule::in(['In-Person', 'Online'])],
            'room_id'        => ['nullable', 'integer', 'exists:Rooms,room_id'],
            'meeting_link'   => ['nullable', 'string', 'max:500'],
            'scheduled_time' => ['nullable', 'string'],
        ];
    }

    /** Map a service result tuple to a JSON response. */
    private function finish(array $result): JsonResponse
    {
        [$ok, $message] = $result;
        return $ok
            ? response()->json(['message' => $message])
            : response()->json(['error' => $message], 422);
    }

    /** Wraps a Resource collection with shared additional() data. */
    private function collectionResponse($requests, array $additional): JsonResponse
    {
        return response()->json([
            'requests' => $requests->map(fn($r) => (new RequestResource($r))->additional($additional)),
        ]);
    }
}
