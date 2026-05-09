<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a TutorProfile (with eager-loaded user, reviews, courses) for the
 * tutor directory list. Replaces the inline ->map(fn($tutor) => [...]) blob
 * that lived in TutorController::index().
 *
 * Required eager-loads: ['user', 'reviews', 'courses']
 */
class TutorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reviewCount = $this->reviews->count();
        $rating      = $reviewCount > 0 ? $this->reviews->avg('rating') : 0.0;

        $first = $this->user?->first_name ?? '';
        $last  = $this->user?->last_name  ?? '';

        return [
            'id'       => $this->user_id,
            'name'     => trim("{$first} {$last}"),
            'initials' => substr($first ?: '?', 0, 1) . substr($last ?: '?', 0, 1),
            'degree'   => trim(($this->user?->program_code ?? '') . ' ' . ($this->user?->current_year_level ?? '')),
            'rating'   => round((float) $rating, 1),
            'reviews'  => $reviewCount,
            'courses'  => $this->courses->pluck('course_code')->unique()->values(),
        ];
    }
}
