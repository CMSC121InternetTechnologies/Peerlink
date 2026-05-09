<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One row per (session, user, role) triple. Previously this table was only
 * touched via raw `DB::table('Session_Participants')->…` strings scattered
 * across SessionController, RequestController, and ReviewController.
 *
 * Having a real model lets us:
 *   - relate sessions ↔ users with `participantUsers()` (already on TutoringSession)
 *   - query attendance with type-safe builder calls
 *   - replace the duplicated raw-insert blocks with $session->participants()->create([…])
 */
class SessionParticipant extends Model
{
    protected $table      = 'Session_Participants';
    protected $primaryKey = 'participation_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null;
    public const CREATED_AT = 'joined_at';

    protected $fillable = [
        'participation_id', 'session_id', 'user_id', 'role', 'has_attended', 'joined_at',
    ];

    protected $casts = [
        'has_attended' => 'boolean',
        'joined_at'    => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model): void {
            if (empty($model->participation_id)) {
                $model->participation_id = (string) Str::uuid();
            }
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TutoringSession::class, 'session_id', 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
