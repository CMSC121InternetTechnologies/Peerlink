<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class TutoringRequest extends Model
{
    protected $table      = 'Requests';
    protected $primaryKey = 'request_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null;

    protected $fillable = [
        'student_id', 'tutor_id', 'course_id', 'message', 'status',
        'counter_proposed_time', 'counter_proposed_message',
        'counter_proposed_modality', 'counter_proposed_room_id',
    ];

    /**
     * Cast the status column to the RequestStatus enum so callers get a
     * type-safe value back instead of a magic string. Eloquent will still
     * read/write the underlying column as the enum's string value.
     */
    protected $casts = [
        'status' => RequestStatus::class,
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model): void {
            if (empty($model->request_id)) {
                $model->request_id = (string) Str::uuid();
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id', 'user_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id', 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function session(): HasOne
    {
        return $this->hasOne(TutoringSession::class, 'request_id', 'request_id');
    }
}
