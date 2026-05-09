<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profile photo for a user. Originally stored as a base64 BLOB in image_data;
 * we now also support storing a file path on disk (storage/app/public/photos/…)
 * so the DB doesn't bloat with binary data.
 *
 * The migration that adds the `image_path` column makes the old `image_data`
 * column nullable so existing rows keep working until they're replaced.
 */
class UserPhoto extends Model
{
    protected $table      = 'User_Photos';
    protected $primaryKey = 'user_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null;
    public const CREATED_AT = 'uploaded_at';

    protected $fillable = ['user_id', 'image_data', 'image_path', 'mime_type', 'uploaded_at'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
