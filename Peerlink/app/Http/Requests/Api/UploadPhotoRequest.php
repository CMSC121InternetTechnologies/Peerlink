<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST /api/user/photo.
 * Hard cap of ~2.8M base64 chars ≈ 2 MB binary; MIME type is verified
 * server-side after decoding (in the controller) regardless of what the
 * Content-Type prefix claims.
 */
class UploadPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'string', 'max:2800000'],
        ];
    }
}
