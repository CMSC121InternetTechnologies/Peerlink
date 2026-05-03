<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'Courses';
    protected $primaryKey = 'course_id';
    
    public $timestamps = false; // Disable created_at and updated_at (for now)

    protected $fillable = [
        'division_id', 
        'course_code', 
        'course_name'
    ];
}