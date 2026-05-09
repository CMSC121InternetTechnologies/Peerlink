<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates PATCH /api/profile (bio + tutorCourses + tuteeCourses).
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bio'            => ['nullable', 'string', 'max:250'],
            'tutorCourses'   => ['nullable', 'array'],
            'tutorCourses.*' => ['string', 'exists:Courses,course_code'],
            'tuteeCourses'   => ['nullable', 'array'],
            'tuteeCourses.*' => ['string', 'exists:Courses,course_code'],
        ];
    }
}
