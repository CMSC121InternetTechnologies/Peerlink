<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SessionReview extends Model
{
    protected $table      = 'Session_Reviews';
    protected $primaryKey = 'review_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['session_id', 'reviewer_id', 'reviewee_id', 'rating', 'feedback'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model): void {
            if (empty($model->review_id)) {
                $model->review_id = (string) Str::uuid();
            }
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TutoringSession::class, 'session_id', 'session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'user_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id', 'user_id');
    }
}
