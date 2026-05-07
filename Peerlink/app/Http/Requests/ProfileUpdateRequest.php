<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:15'],
            'current_year_level' => ['required', 'integer', 'min:1'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // Bug: registration enforced @up.edu.ph but this form did not,
                // allowing users to switch to a non-institutional address.
                // Fix: mirror the same domain constraint used at registration.
                'ends_with:@up.edu.ph',
                Rule::unique(User::class)->ignore($this->user()->user_id, 'user_id'),
            ],
        ];
    }
}