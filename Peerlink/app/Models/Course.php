<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'Courses';
    protected $primaryKey = 'course_id';
    
    public $timestamps = false;

    protected $fillable = [
        'division_id', 
        'course_code', 
        'course_name'
    ];

    public function topics()
    {
        return $this->hasMany(CourseTopic::class, 'course_id', 'course_id');
    }
}