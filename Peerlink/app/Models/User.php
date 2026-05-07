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
        'program_code',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        // Bug: the 'hashed' cast re-hashes on every set, causing double-hashing
        // when controllers already call Hash::make() before assigning.
        // Fix: remove the cast; controllers handle hashing manually and consistently.
        return [];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
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

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_code', 'program_code');
    }

    public function tutorProfile()
    {
        return $this->hasOne(TutorProfile::class, 'user_id', 'user_id');
    }

    public function tuteeCourses()
    {
        return $this->belongsToMany(Course::class, 'Tutee_Courses', 'user_id', 'course_id');
    }
}