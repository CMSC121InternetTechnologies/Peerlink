<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\RequestAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the body of PATCH /api/requests/{id}.
 * The `action` field is now type-checked against the RequestAction enum
 * instead of an inline `Rule::in(['accept', 'decline', …])`.
 */
class RespondToRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'action'           => ['required', new Enum(RequestAction::class)],
            'modality'         => ['nullable', Rule::in(['In-Person', 'Online'])],
            'room_id'          => ['nullable', 'integer', 'exists:Rooms,room_id'],
            'meeting_link'     => ['nullable', 'string', 'max:500'],
            'scheduled_time'   => ['nullable', 'string'],
            'counter_time'     => ['nullable', 'string'],
            'counter_message'  => ['nullable', 'string', 'max:1000'],
            'counter_modality' => ['nullable', Rule::in(['In-Person', 'Online'])],
            'counter_room_id'  => ['nullable', 'integer', 'exists:Rooms,room_id'],
        ];
    }
}
