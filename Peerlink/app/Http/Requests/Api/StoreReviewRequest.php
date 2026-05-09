<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body of POST /api/reviews.
 * Domain-level checks (self-review, completed status, participation) stay
 * in the controller because they need the resolved User and Session models.
 */
class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'session_id'  => ['required', 'string', 'exists:Sessions,session_id'],
            'reviewee_id' => ['required', 'string', 'exists:Users,user_id'],
            'rating'      => ['required', 'integer', 'min:1', 'max:5'],
            'feedback'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
