<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseTopic;
use App\Models\SessionReview;
use App\Models\TutorProfile;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $currentUserId = $request->user()?->user_id;

        $tutors = TutorProfile::whereHas('courses')
            ->when($currentUserId, fn($q) => $q->where('user_id', '!=', $currentUserId))
            ->with(['user', 'reviews', 'courses'])
            ->get();

        $formattedTutors = $tutors->map(function ($tutor) {
            $degree = $tutor->user->program_code . ' ' . $tutor->user->current_year_level;
            $reviewCount = $tutor->reviews->count();
            $rating = $reviewCount > 0 ? $tutor->reviews->avg('rating') : 0.0;
            $courses = $tutor->courses->pluck('course_code')->unique()->values();

            return [
                'id' => $tutor->user_id,
                'name' => $tutor->user->first_name . ' ' . $tutor->user->last_name,
                'initials' => substr($tutor->user->first_name, 0, 1) . substr($tutor->user->last_name, 0, 1),
                'degree' => $degree,
                'rating' => round($rating, 1),
                'reviews' => $reviewCount,
                'courses' => $courses
            ];
        });

        return response()->json(['tutors' => $formattedTutors]);
    }

    // GET /api/tutors/{id}
    public function show(string $id)
    {
        $tutor = TutorProfile::with(['user', 'reviews.reviewer', 'courses'])
            ->findOrFail($id);

        $courses = $tutor->courses->pluck('course_code')->filter()->unique()->values();

        $reviews = $tutor->reviews()->with('reviewer')->orderByDesc('created_at')->limit(20)->get()
            ->map(fn($r) => [
                'rating'       => $r->rating,
                'feedback'     => $r->feedback,
                'reviewerName' => $r->reviewer
                    ? ($r->reviewer->first_name . ' ' . $r->reviewer->last_name)
                    : 'Anonymous',
                'createdAt'    => $r->created_at,
            ]);

        return response()->json([
            'id'          => $tutor->user_id,
            'name'        => $tutor->user->first_name . ' ' . $tutor->user->last_name,
            'initials'    => substr($tutor->user->first_name, 0, 1) . substr($tutor->user->last_name, 0, 1),
            'degree'      => $tutor->user->program_code . ' ' . $tutor->user->current_year_level,
            'bio'         => $tutor->bio ?? '',
            'rating'      => round((float) $tutor->rating_avg, 1),
            'reviewCount' => $tutor->reviews->count(),
            'courses'     => $courses,
            'reviews'     => $reviews,
        ]);
    }

    // GET /api/courses/{code}/topics
    public function topics(string $code)
    {
        $topics = CourseTopic::whereHas('course', fn($q) => $q->where('course_code', $code))
            ->with('course')
            ->orderBy('topic_id')
            ->get()
            ->map(fn($t) => ['topic_id' => $t->topic_id, 'topic_name' => $t->topic_name]);

        return response()->json(['topics' => $topics]);
    }
}