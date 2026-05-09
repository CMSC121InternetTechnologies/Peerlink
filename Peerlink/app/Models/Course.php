<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table      = 'Courses';
    protected $primaryKey = 'course_id';
    public $timestamps    = false;

    protected $fillable = ['division_id', 'course_code', 'course_name'];

    public function topics(): HasMany
    {
        return $this->hasMany(CourseTopic::class, 'course_id', 'course_id');
    }
}
