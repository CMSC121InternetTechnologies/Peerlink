<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    protected $table      = 'Notifications';
    protected $primaryKey = 'notification_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'message', 'request_id', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model): void {
            if (empty($model->notification_id)) {
                $model->notification_id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
