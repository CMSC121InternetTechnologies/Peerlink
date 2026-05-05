<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SessionReview extends Model
{
    protected $table = 'Session_Reviews';
    protected $primaryKey = 'review_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['session_id', 'reviewer_id', 'reviewee_id', 'rating', 'feedback'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->review_id)) {
                $model->review_id = (string) Str::uuid();
            }
        });
    }

    public function session()
    {
        return $this->belongsTo(TutoringSession::class, 'session_id', 'session_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'user_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id', 'user_id');
    }
}
