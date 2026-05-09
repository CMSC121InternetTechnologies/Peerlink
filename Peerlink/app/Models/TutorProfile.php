<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorProfile extends Model
{
    protected $table      = 'Tutor_Profiles';
    protected $primaryKey = 'user_id';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['user_id', 'bio', 'rating_avg'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * @deprecated prefer User::reviewsReceived(). Kept here so existing
     * eager-loads ('with(['reviews', ...])') keep working without a refactor
     * across every controller in the codebase.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(SessionReview::class, 'reviewee_id', 'user_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'Tutor_Expertise', 'user_id', 'course_id');
    }
}
