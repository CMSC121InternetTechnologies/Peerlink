<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    protected $table = 'Notifications';
    protected $primaryKey = 'notification_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'message', 'request_id', 'is_read'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->notification_id)) {
                $model->notification_id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
