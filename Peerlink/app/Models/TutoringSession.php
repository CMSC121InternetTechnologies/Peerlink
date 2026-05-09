<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutoringSession extends Model
{
    protected $table      = 'Sessions';
    protected $primaryKey = 'session_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['request_id', 'modality', 'room_id', 'meeting_link', 'scheduled_time', 'status', 'summary'];

    protected $casts = [
        'status'         => SessionStatus::class,
        'scheduled_time' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model): void {
            if (empty($model->session_id)) {
                $model->session_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(TutoringRequest::class, 'request_id', 'request_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SessionReview::class, 'session_id', 'session_id');
    }

    public function participantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'Session_Participants',
            'session_id',
            'user_id',
            'session_id',
            'user_id',
        )->withPivot('role', 'has_attended', 'joined_at', 'participation_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class, 'session_id', 'session_id');
    }
}
