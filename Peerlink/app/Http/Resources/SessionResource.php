<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\SessionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a TutoringSession for "My Sessions" or "Open Group Sessions".
 * Mode is selected by the controller via `additional(['perspective' => …])`.
 *
 * Required eager-loads:
 *   ['request.course', 'request.student', 'request.tutor', 'room', 'participantUsers']
 *
 * Pre-built lookups passed in via additional():
 *   - $reviewedIds   collection keyed by session_id
 *   - $userId        the viewer's user_id (for "myRole" / "isPartner" calc)
 *   - $joinedIds     collection of session_ids the viewer joined (open list only)
 *   - $roomCapacities collection keyed by room_id (open list only)
 */
class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perspective = $this->additional['perspective'] ?? 'mine';

        return $perspective === 'open'
            ? $this->openListShape()
            : $this->myListShape();
    }

    private function myListShape(): array
    {
        $userId      = $this->additional['userId'] ?? '';
        $reviewedIds = $this->additional['reviewedIds'] ?? collect();

        $myRole  = $this->participantUsers->firstWhere('user_id', $userId)?->pivot->role ?? 'Tutee';
        $isGroup = str_starts_with($this->request?->message ?? '', '[GROUP]');

        if ($isGroup) {
            $tutorUser   = $this->participantUsers->firstWhere('pivot.role', 'Tutor');
            $partner     = $tutorUser;
            $partnerName = $tutorUser
                ? trim($tutorUser->first_name . ' ' . $tutorUser->last_name)
                : 'Group Session';
        } else {
            $partner     = $this->participantUsers->first(fn($u) => $u->user_id !== $userId);
            $partnerName = $partner
                ? trim($partner->first_name . ' ' . $partner->last_name)
                : 'Unknown';
        }

        $hasReview  = isset($reviewedIds[$this->session_id]);
        $tuteeCount = $this->participantUsers->where('pivot.role', 'Tutee')->count();

        return [
            'session_id'    => $this->session_id,
            'request_id'    => $this->request_id,
            'course'        => $this->request?->course?->course_code,
            'courseName'    => $this->request?->course?->course_name,
            'partnerName'   => $partnerName,
            'partnerId'     => $partner?->user_id,
            'myRole'        => $myRole,
            'isGroup'       => $isGroup,
            'tuteeCount'    => $tuteeCount,
            'scheduledTime' => $this->scheduled_time,
            'modality'      => $this->modality,
            'room'          => $this->room?->room_name,
            'meetingLink'   => $this->meeting_link,
            'status'        => $this->status?->value,
            'hasReview'     => $hasReview,
            'canReview'     => $this->status === SessionStatus::Completed && $myRole === 'Tutee' && !$hasReview,
            'tutorId'       => $this->request?->tutor_id,
        ];
    }

    private function openListShape(): array
    {
        $joinedIds      = $this->additional['joinedIds']      ?? collect();
        $roomCapacities = $this->additional['roomCapacities'] ?? collect();

        $tuteeCount    = $this->participantUsers->where('pivot.role', 'Tutee')->count();
        $capacity      = $roomCapacities[$this->room_id] ?? 99;
        $alreadyJoined = $joinedIds->contains($this->session_id);

        return [
            'session_id'    => $this->session_id,
            'course'        => $this->request?->course?->course_code,
            'courseName'    => $this->request?->course?->course_name,
            'tutorName'     => $this->request?->tutor
                ? trim($this->request->tutor->first_name . ' ' . $this->request->tutor->last_name)
                : 'Unknown',
            'tutorId'       => $this->request?->tutor_id,
            'message'       => ltrim(str_replace('[GROUP]', '', $this->request?->message ?? ''), ' '),
            'scheduledTime' => $this->scheduled_time,
            'modality'      => $this->modality,
            'room'          => $this->room?->room_name,
            'meetingLink'   => $this->meeting_link,
            'tuteeCount'    => $tuteeCount,
            'capacity'      => $capacity,
            'full'          => $tuteeCount >= $capacity,
            'alreadyJoined' => $alreadyJoined,
        ];
    }
}
