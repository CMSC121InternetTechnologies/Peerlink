<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutorResource;
use App\Models\CourseTopic;
use App\Models\TutorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUserId = $request->user()?->user_id;

        $tutors = TutorProfile::whereHas('courses')
            ->when($currentUserId, fn($q) => $q->where('user_id', '!=', $currentUserId))
            ->with(['user', 'reviews', 'courses'])
            ->get();

        // Cache-Control hint: browsers may re-use this response for up to 60s.
        // Pairs with the localStorage cache in app.js — both layers can serve
        // a back/forward navigation without hitting the network.
        return response()->json(['tutors' => TutorResource::collection($tutors)])
            ->header('Cache-Control', 'private, max-age=60');
    }

    /** GET /api/tutors/{id} */
    public function show(string $id): JsonResponse
    {
        $tutor   = TutorProfile::with(['user', 'reviews.reviewer', 'courses'])->findOrFail($id);
        $courses = $tutor->courses->pluck('course_code')->filter()->unique()->values();

        // The eager-loaded reviews collection already has reviewer attached;
        // sort/slice in PHP to avoid a second SQL round-trip.
        $reviews = $tutor->reviews
            ->sortByDesc('created_at')
            ->take(20)
            ->map(fn($r) => [
                'rating'       => $r->rating,
                'feedback'     => $r->feedback,
                'reviewerName' => $r->reviewer
                    ? trim($r->reviewer->first_name . ' ' . $r->reviewer->last_name)
                    : 'Anonymous',
                'createdAt'    => $r->created_at,
            ])
            ->values();

        $first = $tutor->user?->first_name ?? '';
        $last  = $tutor->user?->last_name  ?? '';

        return response()->json([
            'id'          => $tutor->user_id,
            'name'        => trim("{$first} {$last}"),
            'initials'    => substr($first ?: '?', 0, 1) . substr($last ?: '?', 0, 1),
            'degree'      => trim(($tutor->user?->program_code ?? '') . ' ' . ($tutor->user?->current_year_level ?? '')),
            'bio'         => $tutor->bio ?? '',
            'rating'      => round((float) $tutor->rating_avg, 1),
            'reviewCount' => $tutor->reviews->count(),
            'courses'     => $courses,
            'reviews'     => $reviews,
        ]);
    }

    /** GET /api/courses/{code}/topics */
    public function topics(string $code): JsonResponse
    {
        $topics = CourseTopic::whereHas('course', fn($q) => $q->where('course_code', $code))
            ->orderBy('topic_id')
            ->get(['topic_id', 'topic_name', 'course_id'])
            ->map(fn($t) => ['topic_id' => $t->topic_id, 'topic_name' => $t->topic_name]);

        return response()->json(['topics' => $topics]);
    }
}
