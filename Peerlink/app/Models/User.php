<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public const UPDATED_AT = null; // Disable updated_at since database only has created_at (for now)

    protected $fillable = [
        'email',
        'password_hash',
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'current_year_level',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Disable "Remember Me" tokens as they do not exist in the database schema (for now)
    public function getRememberTokenName()
    {
        return '';
    }

    // Auto-generate UUID when creating a new user via Laravel
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}