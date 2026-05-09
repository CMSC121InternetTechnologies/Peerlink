<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table      = 'Users';
    protected $primaryKey = 'user_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public const UPDATED_AT = null; // Disable updated_at since DB only has created_at

    protected $fillable = [
        'email', 'password_hash',
        'first_name', 'middle_name', 'last_name',
        'contact_number', 'current_year_level', 'program_code',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        // The 'hashed' cast was removed because controllers already call
        // Hash::make() before assigning, and the cast would double-hash.
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

    /**
     * "Remember Me" tokens aren't in the DB schema. Returning null (rather
     * than '') tells Laravel's Auth there's no remember-me column at all,
     * which avoids edge cases in middleware that probe for the column name.
     */
    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /** Auto-generate a UUID primary key on insert. */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_code', 'program_code');
    }

    public function tutorProfile(): HasOne
    {
        return $this->hasOne(TutorProfile::class, 'user_id', 'user_id');
    }

    public function tuteeCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'Tutee_Courses', 'user_id', 'course_id');
    }

    /**
     * Reviews about this user (received from past tutees).
     * Moved here from TutorProfile because reviews are conceptually about a
     * person, not about the tutor-profile row.
     */
    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(SessionReview::class, 'reviewee_id', 'user_id');
    }
}
