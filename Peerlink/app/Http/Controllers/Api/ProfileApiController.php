<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Room;
use App\Models\TutorProfile;
use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        // Bug: MIME was hardcoded as image/jpeg; PNG/WebP uploads would render broken.
        // Fix: use the mime_type stored during upload (falls back to image/jpeg for rows
        // that existed before the mime_type column was added).
        $photoUrl = $photo
            ? ('data:' . ($photo->mime_type ?? 'image/jpeg') . ';base64,' . base64_encode($photo->image_data))
            : null;

        return response()->json([
            'bio'              => $tutorProfile?->bio ?? '',
            'isTutor'          => $tutorProfile !== null,
            'tutorCourses'     => $tutorCourses,
            'tuteeCourses'     => $user->tuteeCourses()->pluck('course_code')->toArray(),
            'coursesCount'     => count($tutorCourses),
            'ratingAvg'        => $tutorProfile ? (float) $tutorProfile->rating_avg : 0.0,
            'upcomingSessions' => $upcomingSessions,
            // Rooms list is read on every profile fetch but virtually never changes.
            // Cache for 1 hour using Laravel's file cache. Any admin tool that
            // adds/edits rooms should call Cache::forget('peerlink.rooms') after.
            'rooms'            => Cache::remember(
                'peerlink.rooms',
                3600,
                fn() => Room::all(['room_id', 'room_code', 'room_name', 'room_type'])
            ),
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

        $tutorProfile = TutorProfile::where('user_id', $user->user_id)->first();

        $hasTutorContent = !empty($validated['bio'])
            || (!empty($validated['tutorCourses']) && count($validated['tutorCourses']) > 0);

        if ($hasTutorContent) {
            if (!$tutorProfile) {
                $tutorProfile = TutorProfile::create([
                    'user_id'    => $user->user_id,
                    'bio'        => $validated['bio'] ?? '',
                    'rating_avg' => 0,
                ]);
            } else {
                $tutorProfile->bio = $validated['bio'] ?? '';
                $tutorProfile->save();
            }
        } elseif ($tutorProfile && array_key_exists('bio', $validated)) {
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

        if (array_key_exists('tuteeCourses', $validated)) {
            $tuteeCourseIds = Course::whereIn('course_code', $validated['tuteeCourses'] ?? [])
                ->pluck('course_id')
                ->toArray();

            DB::table('Tutee_Courses')->where('user_id', $user->user_id)->delete();
            if (!empty($tuteeCourseIds)) {
                DB::table('Tutee_Courses')->insert(
                    array_map(fn($cid) => ['user_id' => $user->user_id, 'course_id' => $cid], $tuteeCourseIds)
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

        // Bug: 'string' alone placed no cap on payload size (memory exhaustion)
        // and any binary data could be stored, not just images.
        // Fix: enforce a strict max length (≈ 2 MB base64 ≈ 2_800_000 chars)
        // and detect the real MIME type server-side after decoding.
        $request->validate([
            'photo' => ['required', 'string', 'max:2800000'],
        ]);

        $dataUrl = $request->input('photo');

        // Extract the declared MIME type and the raw base64 payload.
        if (!preg_match('/^data:(image\/\w+);base64,(.+)$/', $dataUrl, $matches)) {
            return response()->json(['error' => 'Invalid image data URL format.'], 422);
        }

        $base64 = $matches[2];
        $binary = base64_decode($base64, strict: true);

        // Bug: base64_decode returns false OR an empty string for bad input;
        // !$binary catches both. Using strict: true returns false on non-base64 chars.
        if ($binary === false || $binary === '') {
            return response()->json(['error' => 'Invalid base64 image data.'], 422);
        }

        // Bug: MIME type was hardcoded as image/jpeg on read-back regardless of
        // what was uploaded. Fix: detect the real MIME from the decoded binary and
        // store it alongside the data so it can be served correctly.
        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($detectedMime, $allowedMimes, true)) {
            return response()->json(['error' => 'Only JPEG, PNG, GIF, and WebP images are allowed.'], 422);
        }

        DB::table('User_Photos')->updateOrInsert(
            ['user_id' => $user->user_id],
            ['image_data' => $binary, 'mime_type' => $detectedMime, 'uploaded_at' => now()]
        );

        return response()->json(['message' => 'Photo uploaded.']);
    }

    // Bug: showPhoto() was defined but had no route registered in web.php or api.php,
    // making it permanently unreachable (dead code). The photo URL is already returned
    // by show(), so this duplicate method is removed entirely.
}
