<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TutoringSession extends Model
{
    protected $table = 'Sessions';
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['request_id', 'modality', 'room_id', 'meeting_link', 'scheduled_time', 'status'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->session_id)) {
                $model->session_id = (string) Str::uuid();
            }
        });
    }

    public function request()
    {
        return $this->belongsTo(TutoringRequest::class, 'request_id', 'request_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function reviews()
    {
        return $this->hasMany(SessionReview::class, 'session_id', 'session_id');
    }
}
