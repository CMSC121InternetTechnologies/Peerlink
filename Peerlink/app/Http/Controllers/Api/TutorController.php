<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TutorProfile;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index()
    {
        $tutors = TutorProfile::with(['user', 'reviews', 'topics.course'])->get();

        $formattedTutors = $tutors->map(function ($tutor) {
            
            $degree = $tutor->user->program_code . ' ' . $tutor->user->current_year_level;
            
            $reviewCount = $tutor->reviews->count();
            $rating = $reviewCount > 0 ? $tutor->reviews->avg('rating') : 0.0;

            $courses = $tutor->topics->map(function($topic) {
                return str_replace(' ', '', strtoupper($topic->course->course_code));
            })->unique()->values();

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
}