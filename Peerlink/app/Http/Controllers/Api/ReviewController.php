<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SessionReview;
use App\Models\TutorProfile;
use App\Models\TutoringSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    // POST /api/reviews
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'session_id'  => ['required', 'string', 'exists:Sessions,session_id'],
            'reviewee_id' => ['required', 'string', 'exists:Users,user_id'],
            'rating'      => ['required', 'integer', 'min:1', 'max:5'],
            'feedback'    => ['nullable', 'string', 'max:1000'],
        ]);

        // Bug #4: no check prevented a user from submitting a review for themselves.
        // Fix: reject the request when reviewer_id === reviewee_id.
        if ($user->user_id === $validated['reviewee_id']) {
            return response()->json(['error' => 'You cannot review yourself.'], 422);
        }

        // Bug #5: reviews could be submitted on Scheduled or Cancelled sessions.
        // Fix: load the session and confirm it is Completed before proceeding.
        $session = \App\Models\TutoringSession::where('session_id', $validated['session_id'])->firstOrFail();
        if ($session->status !== 'Completed') {
            return response()->json(['error' => 'Reviews can only be submitted for completed sessions.'], 422);
        }

        // Prevent duplicate reviews
        if (SessionReview::where('session_id', $validated['session_id'])
                ->where('reviewer_id', $user->user_id)
                ->exists()) {
            return response()->json(['error' => 'You have already reviewed this session.'], 422);
        }

        // Verify the reviewer participated in this session
        $participated = DB::table('Session_Participants')
            ->where('session_id', $validated['session_id'])
            ->where('user_id', $user->user_id)
            ->exists();

        if (!$participated) {
            return response()->json(['error' => 'You did not participate in this session.'], 403);
        }

        // Bug #6: only the reviewer's participation was verified; any user_id could be
        // passed as reviewee_id. Fix: also confirm the reviewee was in the same session.
        $revieweeParticipated = DB::table('Session_Participants')
            ->where('session_id', $validated['session_id'])
            ->where('user_id', $validated['reviewee_id'])
            ->exists();

        if (!$revieweeParticipated) {
            return response()->json(['error' => 'The reviewee did not participate in this session.'], 403);
        }

        SessionReview::create([
            'session_id'  => $validated['session_id'],
            'reviewer_id' => $user->user_id,
            'reviewee_id' => $validated['reviewee_id'],
            'rating'      => $validated['rating'],
            'feedback'    => $validated['feedback'] ?? null,
        ]);

        // Recalculate and persist rating_avg for the reviewee's tutor profile
        $avg = SessionReview::where('reviewee_id', $validated['reviewee_id'])
            ->avg('rating');

        TutorProfile::where('user_id', $validated['reviewee_id'])
            ->update(['rating_avg' => round($avg, 2)]);

        return response()->json(['message' => 'Review submitted.'], 201);
    }

    // GET /api/reviews?tutor_id=X
    public function index(Request $request)
    {
        $tutorId = $request->query('tutor_id');
        if (!$tutorId) {
            return response()->json(['error' => 'tutor_id is required.'], 422);
        }

        $reviews = SessionReview::where('reviewee_id', $tutorId)
            ->with('reviewer')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'reviews' => $reviews->map(fn($r) => [
                'rating'       => $r->rating,
                'feedback'     => $r->feedback,
                'reviewerName' => $r->reviewer
                    ? ($r->reviewer->first_name . ' ' . $r->reviewer->last_name)
                    : 'Anonymous',
                'createdAt'    => $r->created_at,
            ]),
        ]);
    }
}
