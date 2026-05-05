<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Room;
use App\Models\TutorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileApiController extends Controller
{
    // GET /api/profile
    public function show(Request $request)
    {
        $user = $request->user();
        $tutorProfile = TutorProfile::find($user->user_id);

        $tutorCourses = [];
        if ($tutorProfile) {
            $tutorCourses = $tutorProfile->topics()
                ->with('course')
                ->get()
                ->pluck('course.course_code')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        return response()->json([
            'bio'         => $tutorProfile?->bio ?? '',
            'isTutor'     => $tutorProfile !== null,
            'tutorCourses'=> $tutorCourses,
            'rooms'       => Room::all(['room_id', 'room_code', 'room_name', 'room_type']),
        ]);
    }

    // PATCH /api/profile
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'bio'            => ['nullable', 'string', 'max:250'],
            'tutorCourses'   => ['nullable', 'array'],
            'tutorCourses.*' => ['string', 'exists:Courses,course_code'],
        ]);

        $tutorProfile = TutorProfile::firstOrCreate(
            ['user_id' => $user->user_id],
            ['bio' => '', 'rating_avg' => 0]
        );
        $tutorProfile->bio = $validated['bio'] ?? '';
        $tutorProfile->save();

        if (array_key_exists('tutorCourses', $validated)) {
            $courseIds = Course::whereIn('course_code', $validated['tutorCourses'] ?? [])
                ->pluck('course_id')
                ->toArray();

            $topicIds = CourseTopic::whereIn('course_id', $courseIds)
                ->pluck('topic_id')
                ->toArray();

            DB::table('Tutor_Expertise')->where('user_id', $user->user_id)->delete();
            if (!empty($topicIds)) {
                DB::table('Tutor_Expertise')->insert(
                    array_map(fn($tid) => ['user_id' => $user->user_id, 'topic_id' => $tid], $topicIds)
                );
            }
        }

        return response()->json(['message' => 'Profile updated.']);
    }
}
