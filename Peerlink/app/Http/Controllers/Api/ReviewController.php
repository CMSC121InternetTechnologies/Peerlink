<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReviewRequest;
use App\Models\SessionParticipant;
use App\Models\SessionReview;
use App\Models\TutorProfile;
use App\Models\TutoringSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** POST /api/reviews */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        if ($user->user_id === $validated['reviewee_id']) {
            return response()->json(['error' => 'You cannot review yourself.'], 422);
        }

        $session = TutoringSession::where('session_id', $validated['session_id'])->firstOrFail();
        if ($session->status !== SessionStatus::Completed) {
            return response()->json(['error' => 'Reviews can only be submitted for completed sessions.'], 422);
        }

        $alreadyReviewed = SessionReview::where('session_id', $validated['session_id'])
            ->where('reviewer_id', $user->user_id)
            ->exists();
        if ($alreadyReviewed) {
            return response()->json(['error' => 'You have already reviewed this session.'], 422);
        }

        $reviewerParticipated = SessionParticipant::where('session_id', $validated['session_id'])
            ->where('user_id', $user->user_id)
            ->exists();
        if (!$reviewerParticipated) {
            return response()->json(['error' => 'You did not participate in this session.'], 403);
        }

        $revieweeParticipated = SessionParticipant::where('session_id', $validated['session_id'])
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

        // Recalculate and persist rating_avg for the reviewee's tutor profile.
        $avg = SessionReview::where('reviewee_id', $validated['reviewee_id'])->avg('rating');
        TutorProfile::where('user_id', $validated['reviewee_id'])
            ->update(['rating_avg' => round((float) $avg, 2)]);

        return response()->json(['message' => 'Review submitted.'], 201);
    }

    /** GET /api/reviews?tutor_id=X */
    public function index(Request $request): JsonResponse
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
                    ? trim($r->reviewer->first_name . ' ' . $r->reviewer->last_name)
                    : 'Anonymous',
                'createdAt'    => $r->created_at,
            ]),
        ]);
    }
}
