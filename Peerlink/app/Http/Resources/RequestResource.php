<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\RequestStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a TutoringRequest for either the student's "My Requests" view or
 * the tutor's "Incoming" / "Broadcast Pool" views. The presence of certain
 * pieces (counter-proposal block, attached session) varies by perspective,
 * so we expose them via the `additional()` shape passed by the controller.
 *
 * Required eager-loads (student view): ['tutor', 'course', 'session.room']
 * Required eager-loads (tutor view):   ['student', 'course']
 *
 * The controller passes pre-built lookup tables via the resource's
 * `additional()` so this class never fires N+1 queries:
 *   - $counterRooms       (room_id => room_name)
 *   - $reviewedSessionIds (collection keyed by session_id)
 */
class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perspective = $this->additional['perspective'] ?? 'student';

        if ($perspective === 'tutor') {
            return $this->forTutor();
        }
        if ($perspective === 'broadcast') {
            return $this->forBroadcast();
        }
        return $this->forStudent();
    }

    private function forTutor(): array
    {
        return [
            'id'        => $this->request_id,
            'tuteeName' => $this->student
                ? trim($this->student->first_name . ' ' . $this->student->last_name)
                : 'Unknown',
            'topic'     => ($this->course?->course_code ?? '') . ' — ' . ($this->course?->course_name ?? ''),
            'course'    => $this->course?->course_code,
            'date'      => $this->created_at,
            'message'   => $this->message,
            'status'    => $this->status?->value,
        ];
    }

    private function forBroadcast(): array
    {
        return [
            'id'          => $this->request_id,
            'studentName' => $this->student
                ? trim($this->student->first_name . ' ' . $this->student->last_name)
                : 'Unknown',
            'course'      => $this->course?->course_code,
            'topic'       => $this->course?->course_name ?? '',
            'message'     => $this->message,
            'date'        => $this->created_at,
        ];
    }

    private function forStudent(): array
    {
        $counterRooms       = $this->additional['counterRooms']       ?? collect();
        $reviewedSessionIds = $this->additional['reviewedSessionIds'] ?? collect();

        return [
            'id'        => $this->request_id,
            'course'    => $this->course?->course_code,
            'tutorName' => $this->tutor
                ? trim($this->tutor->first_name . ' ' . $this->tutor->last_name)
                : 'Broadcast',
            'tutorId'   => $this->tutor_id,
            'status'    => $this->status?->value,
            'message'   => $this->message,
            'createdAt' => $this->created_at,
            'counterProposal' => $this->status === RequestStatus::CounterProposed ? [
                'proposedTime' => $this->counter_proposed_time,
                'message'      => $this->counter_proposed_message,
                'modality'     => $this->counter_proposed_modality,
                'room'         => $this->counter_proposed_room_id
                    ? ($counterRooms[$this->counter_proposed_room_id] ?? null)
                    : null,
            ] : null,
            'session' => $this->session ? [
                'session_id'    => $this->session->session_id,
                'modality'      => $this->session->modality,
                'room'          => $this->session->room?->room_name,
                'scheduledTime' => $this->session->scheduled_time,
                'hasReview'     => isset($reviewedSessionIds[$this->session->session_id]),
            ] : null,
        ];
    }
}
