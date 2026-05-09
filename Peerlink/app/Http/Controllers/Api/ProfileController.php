<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\UploadPhotoRequest;
use App\Models\Course;
use App\Models\Room;
use App\Models\TutorProfile;
use App\Models\TutoringSession;
use App\Models\UserPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user         = $request->user();
        $tutorProfile = TutorProfile::where('user_id', $user->user_id)->first();

        $tutorCourses = $tutorProfile
            ? $tutorProfile->courses()->pluck('course_code')->filter()->unique()->values()->toArray()
            : [];

        $upcomingSessions = $tutorProfile
            ? TutoringSession::whereHas('request', fn($q) => $q->where('tutor_id', $user->user_id))
                ->where('status', SessionStatus::Scheduled->value)
                ->where('scheduled_time', '>', now())
                ->count()
            : 0;

        return response()->json([
            'bio'              => $tutorProfile?->bio ?? '',
            'isTutor'          => $tutorProfile !== null,
            'tutorCourses'     => $tutorCourses,
            'tuteeCourses'     => $user->tuteeCourses()->pluck('course_code')->toArray(),
            'coursesCount'     => count($tutorCourses),
            'ratingAvg'        => $tutorProfile ? (float) $tutorProfile->rating_avg : 0.0,
            'upcomingSessions' => $upcomingSessions,
            // Rooms list is read on every profile fetch but virtually never changes;
            // file-cached for 1 hour. Forget the key from any admin tool that mutates it.
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
            'photoUrl'         => $this->resolvePhotoUrl($user->user_id),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        $tutorProfile = TutorProfile::where('user_id', $user->user_id)->first();

        $hasTutorContent = !empty($validated['bio'])
            || (!empty($validated['tutorCourses']) && count($validated['tutorCourses']) > 0);

        if ($hasTutorContent) {
            $tutorProfile = $tutorProfile ?? TutorProfile::create([
                'user_id'    => $user->user_id,
                'bio'        => $validated['bio'] ?? '',
                'rating_avg' => 0,
            ]);
            $tutorProfile->bio = $validated['bio'] ?? $tutorProfile->bio;
            $tutorProfile->save();
        } elseif ($tutorProfile && array_key_exists('bio', $validated)) {
            $tutorProfile->bio = '';
            $tutorProfile->save();
        }

        // Sync expertise / tutee courses via the Eloquent BelongsToMany relations
        // rather than raw DB::table inserts. sync() handles the delete-then-insert
        // diff for us and uses the pivot's column names from the relation
        // definition, so we don't have to spell them out twice.
        if (array_key_exists('tutorCourses', $validated)) {
            $courseIds = Course::whereIn('course_code', $validated['tutorCourses'] ?? [])
                ->pluck('course_id')->toArray();
            // Tutor_Expertise has a FK on tutor_profiles.user_id, so we need a
            // TutorProfile to attach to. The earlier branch already created one
            // when there was tutor content, but if the user is clearing their
            // tutor courses entirely we may not have one — guard against null.
            if ($tutorProfile) {
                $tutorProfile->courses()->sync($courseIds);
            }
        }

        if (array_key_exists('tuteeCourses', $validated)) {
            $tuteeCourseIds = Course::whereIn('course_code', $validated['tuteeCourses'] ?? [])
                ->pluck('course_id')->toArray();
            $user->tuteeCourses()->sync($tuteeCourseIds);
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    /** PATCH /api/user/profile — personal info (year level, program, contact). */
    public function updatePersonal(Request $request): JsonResponse
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

    /** POST /api/user/password */
    public function changePassword(Request $request): JsonResponse
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

    /**
     * POST /api/user/photo
     *
     * Accepts a data-URL ("data:image/png;base64,…") and stores the binary
     * to disk under storage/app/public/photos/{user_id}.{ext}, then writes
     * the path into User_Photos.image_path.
     *
     * Replaces an older flow that base64-stuffed the binary directly into
     * a BLOB column, which bloated the DB and forced a base64 round-trip
     * on every profile fetch.
     */
    public function uploadPhoto(UploadPhotoRequest $request): JsonResponse
    {
        $user    = $request->user();
        $dataUrl = $request->input('photo');

        if (!preg_match('/^data:(image\/\w+);base64,(.+)$/', $dataUrl, $matches)) {
            return response()->json(['error' => 'Invalid image data URL format.'], 422);
        }

        $binary = base64_decode($matches[2], strict: true);
        if ($binary === false || $binary === '') {
            return response()->json(['error' => 'Invalid base64 image data.'], 422);
        }

        // Detect MIME from the decoded bytes — we don't trust the data-URL
        // prefix because it's user-controlled. Reject anything that isn't an
        // image format we want to serve.
        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($allowedMimes[$detectedMime])) {
            return response()->json(['error' => 'Only JPEG, PNG, GIF, and WebP images are allowed.'], 422);
        }

        // Defense-in-depth: even though $user->user_id is server-resolved from
        // the session (not request input), validate the UUID shape before
        // interpolating it into a filesystem path. This blocks any future
        // refactor that accidentally exposes user_id to user input.
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $user->user_id)) {
            return response()->json(['error' => 'Invalid account state — please log in again.'], 422);
        }

        $ext  = $allowedMimes[$detectedMime];
        $path = "photos/{$user->user_id}.{$ext}";

        // Replace any prior photo for this user — different extension means
        // a stale file would be left behind, so we sweep all variants.
        foreach ($allowedMimes as $oldExt) {
            $candidate = "photos/{$user->user_id}.{$oldExt}";
            if (Storage::disk('public')->exists($candidate)) {
                Storage::disk('public')->delete($candidate);
            }
        }

        Storage::disk('public')->put($path, $binary);

        UserPhoto::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'image_data' => null,
                'image_path' => $path,
                'mime_type'  => $detectedMime,
                'uploaded_at' => now(),
            ]
        );

        return response()->json([
            'message'  => 'Photo uploaded.',
            'photoUrl' => $this->resolvePhotoUrl($user->user_id),
        ]);
    }

    /**
     * DELETE /api/user/photo — remove the user's profile photo.
     * NEW endpoint: was previously impossible to clear a photo without
     * uploading another over it.
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $user  = $request->user();
        $photo = UserPhoto::where('user_id', $user->user_id)->first();

        if (!$photo) {
            return response()->json(['message' => 'No photo to remove.']);
        }

        if ($photo->image_path && Storage::disk('public')->exists($photo->image_path)) {
            Storage::disk('public')->delete($photo->image_path);
        }
        $photo->delete();

        return response()->json(['message' => 'Photo removed.']);
    }

    /**
     * Resolves the right URL for the user's photo:
     *   1. New rows: served from /storage/photos/… (file on disk)
     *   2. Legacy rows: still served as a base64 data-URL from image_data
     *   3. No row: null (frontend shows initials)
     */
    private function resolvePhotoUrl(string $userId): ?string
    {
        $photo = UserPhoto::where('user_id', $userId)->first();
        if (!$photo) {
            return null;
        }
        if ($photo->image_path) {
            // asset() respects APP_URL, so the URL works for both `php artisan
            // serve` (127.0.0.1:8000) and a deployed domain.
            return URL::asset('storage/' . $photo->image_path);
        }
        if ($photo->image_data) {
            return 'data:' . ($photo->mime_type ?? 'image/jpeg') . ';base64,' . base64_encode($photo->image_data);
        }
        return null;
    }
}
