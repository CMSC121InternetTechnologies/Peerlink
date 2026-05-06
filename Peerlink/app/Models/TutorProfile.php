<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TutorProfile extends Model
{
    protected $table = 'Tutor_Profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['user_id', 'bio', 'rating_avg'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(SessionReview::class, 'reviewee_id', 'user_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'Tutor_Expertise', 'user_id', 'course_id');
    }
}