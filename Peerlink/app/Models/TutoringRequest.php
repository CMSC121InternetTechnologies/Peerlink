<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TutoringRequest extends Model
{
    protected $table = 'Requests';
    protected $primaryKey = 'request_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public const UPDATED_AT = null;

    protected $fillable = [
        'student_id', 'tutor_id', 'course_id', 'message', 'status',
        'counter_proposed_time', 'counter_proposed_message',
        'counter_proposed_modality', 'counter_proposed_room_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->request_id)) {
                $model->request_id = (string) Str::uuid();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id', 'user_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id', 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function session()
    {
        return $this->hasOne(TutoringSession::class, 'request_id', 'request_id');
    }
}
