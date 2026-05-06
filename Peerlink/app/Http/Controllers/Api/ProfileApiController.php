<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Room;
use App\Models\TutorProfile;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileApiController extends Controller
{
    // GET /api/profile
    public function show(Request $request)
    {
        $user         = $request->user();
        $tutorProfile = TutorProfile::find($user->user_id);

        $tutorCourses = [];
        
        if ($tutorProfile) $tutorCourses = $tutorProfile->courses()->pluck('course_code')->toArray();

        // Tutor dashboard stats
        $upcomingSessions = 0;
        if ($tutorProfile) {
            $upcomingSessions = TutoringSession::whereHas('request', function ($q) use ($user) {
                $q->where('tutor_id', $user->user_id);
            })
            ->where('status', 'Scheduled')
            ->where('scheduled_time', '>', now())
            ->count();
        }

        return response()->json([
            'userId'           => $user->user_id,
            'hasPhoto'         => \App\Models\UserPhoto::where('user_id', $user->user_id)->exists(),
            'bio'              => $tutorProfile?->bio ?? '',
            'isTutor'          => $tutorProfile !== null,
            'tutorCourses'     => $tutorCourses,
            'tuteeCourses'     => $user->tuteeCourses()->pluck('course_code')->toArray(),
            'coursesCount'     => count($tutorCourses),
            'ratingAvg'        => $tutorProfile ? (float) $tutorProfile->rating_avg : 0.0,
            'upcomingSessions' => $upcomingSessions,
            'rooms'            => Room::all(['room_id', 'room_code', 'room_name', 'room_type']),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'bio'            => ['nullable', 'string', 'max:250'],
            'tutorCourses'   => ['nullable', 'array'],
            'tutorCourses.*' => ['string', 'exists:Courses,course_code'],
            'tuteeCourses'   => ['nullable', 'array'],
            'tuteeCourses.*' => ['string', 'exists:Courses,course_code'],
        ]);

        $tutorProfile = TutorProfile::where('user_id', $user->user_id)->first();
        if (!$tutorProfile) {
            $tutorProfile = new TutorProfile();
            $tutorProfile->user_id = $user->user_id;
            $tutorProfile->rating_avg = 0;
        }
        $tutorProfile->bio = $validated['bio'] ?? '';
        $tutorProfile->save();

        if ($request->has('tutorCourses')) {
            $courseIds = Course::whereIn('course_code', $validated['tutorCourses'] ?? [])->pluck('course_id')->toArray();
            DB::table('Tutor_Expertise')->where('user_id', $user->user_id)->delete();
            
            foreach ($courseIds as $cid) {
                DB::table('Tutor_Expertise')->insert([
                    'user_id' => $user->user_id, 
                    'course_id' => $cid
                ]);
            }
        }

        if ($request->has('tuteeCourses')) {
            $tuteeIds = Course::whereIn('course_code', $validated['tuteeCourses'] ?? [])->pluck('course_id')->toArray();
            DB::table('Tutee_Courses')->where('user_id', $user->user_id)->delete();
            
            foreach ($tuteeIds as $cid) {
                DB::table('Tutee_Courses')->insert([
                    'user_id' => $user->user_id, 
                    'course_id' => $cid
                ]);
            }
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }
}
