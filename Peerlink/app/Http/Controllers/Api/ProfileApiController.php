<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Room;
use App\Models\TutorProfile;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileApiController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $tutorProfile = \App\Models\TutorProfile::where('user_id', $user->user_id)->first();
        $tutorCourses = [];
        if ($tutorProfile) {
            $tutorCourses = $tutorProfile->courses()
                ->pluck('course_code')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        $upcomingSessions = 0;
        if ($tutorProfile) {
            $upcomingSessions = \App\Models\TutoringSession::whereHas('request', function ($q) use ($user) {
                $q->where('tutor_id', $user->user_id);
            })->where('status', 'Scheduled')->where('scheduled_time', '>', now())->count();
        }

        $photo = DB::table('User_Photos')->where('user_id', $user->user_id)->first();
        $photoUrl = $photo ? ('data:image/jpeg;base64,' . base64_encode($photo->image_data)) : null;

        return response()->json([
            'bio'              => $tutorProfile?->bio ?? '',
            'isTutor'          => $tutorProfile !== null,
            'tutorCourses'     => $tutorCourses,
            'tuteeCourses'     => $user->tuteeCourses()->pluck('course_code')->toArray(),
            'coursesCount'     => count($tutorCourses),
            'ratingAvg'        => $tutorProfile ? (float) $tutorProfile->rating_avg : 0.0,
            'upcomingSessions' => $upcomingSessions,
            'rooms'            => Room::all(['room_id', 'room_code', 'room_name', 'room_type']),
            'firstName'        => $user->first_name,
            'lastName'         => $user->last_name,
            'programCode'      => $user->program_code,
            'yearLevel'        => $user->current_year_level,
            'contactNumber'    => $user->contact_number,
            'photoUrl'         => $photoUrl,
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

        $hasTutorContent = !empty($validated['bio'])
            || (!empty($validated['tutorCourses']) && count($validated['tutorCourses']) > 0);

        $tutorProfile = TutorProfile::find($user->user_id);

        if ($hasTutorContent) {
            if (!$tutorProfile) {
                $tutorProfile = TutorProfile::create([
                    'user_id'    => $user->user_id,
                    'bio'        => '',
                    'rating_avg' => 0,
                ]);
            }
            $tutorProfile->bio = $validated['bio'] ?? '';
            $tutorProfile->save();
        } elseif ($tutorProfile && array_key_exists('bio', $validated)) {
            // Allow clearing bio on existing profile without creating a new one
            $tutorProfile->bio = '';
            $tutorProfile->save();
        }

        if (array_key_exists('tutorCourses', $validated)) {
            $courseIds = Course::whereIn('course_code', $validated['tutorCourses'] ?? [])
                ->pluck('course_id')
                ->toArray();

            DB::table('Tutor_Expertise')->where('user_id', $user->user_id)->delete();
            if (!empty($courseIds)) {
                DB::table('Tutor_Expertise')->insert(
                    array_map(fn($cid) => ['user_id' => $user->user_id, 'course_id' => $cid], $courseIds)
                );
            }
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    // PATCH /api/user/profile — update personal info (year level, program)
    public function updatePersonal(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_year_level' => ['required', 'integer', 'min:1', 'max:10'],
            'program_code'       => ['required', 'string', 'exists:Programs,program_code'],
            'contact_number'     => ['nullable', 'string', 'max:15'],
        ]);

        $user->current_year_level = $validated['current_year_level'];
        $user->program_code       = $validated['program_code'];
        $user->contact_number     = $validated['contact_number'] ?? $user->contact_number;
        $user->save();

        return response()->json(['message' => 'Personal info updated.']);
    }

    // POST /api/user/password — change password
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json(['error' => 'Current password is incorrect.'], 422);
        }

        $user->password_hash = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    // POST /api/user/photo — upload profile picture (base64)
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo' => ['required', 'string'], // base64 data URL
        ]);

        $dataUrl = $request->input('photo');
        // Strip the data URL prefix (data:image/jpeg;base64,...)
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
        $binary = base64_decode($base64);

        if (!$binary) {
            return response()->json(['error' => 'Invalid image data.'], 422);
        }

        DB::table('User_Photos')->updateOrInsert(
            ['user_id' => $user->user_id],
            ['image_data' => $binary, 'uploaded_at' => now()]
        );

        return response()->json(['message' => 'Photo uploaded.']);
    }

    // GET /api/profile — also return photo and personal info
    public function showPhoto(Request $request)
    {
        $user  = $request->user();
        $photo = DB::table('User_Photos')->where('user_id', $user->user_id)->first();

        if (!$photo) {
            return response()->json(['photo' => null]);
        }

        $base64 = base64_encode($photo->image_data);
        return response()->json(['photo' => 'data:image/jpeg;base64,' . $base64]);
    }
}
