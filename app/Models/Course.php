<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $table = 'mdl_course';

    public function section(){
        return $this->hasMany(CourseSection::class, 'course');
    }

}
