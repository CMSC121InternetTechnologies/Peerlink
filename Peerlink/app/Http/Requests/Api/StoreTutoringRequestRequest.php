<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body of POST /api/requests.
 * Pulls validation out of RequestController::store() so the controller
 * focuses on orchestration, not field-by-field validation.
 */
class StoreTutoringRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'course_code'    => ['required', 'string', 'exists:Courses,course_code'],
            'tutor_id'       => ['nullable', 'string', 'exists:Users,user_id'],
            'message'        => ['nullable', 'string', 'max:1000'],
            'preferred_date' => ['nullable', 'date', 'after:now'],
        ];
    }
}
