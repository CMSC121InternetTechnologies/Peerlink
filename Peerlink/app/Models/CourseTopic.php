<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CourseTopic extends Model
{
    protected $table = 'Course_Topics';
    protected $primaryKey = 'topic_id';
    public $timestamps = false;

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}