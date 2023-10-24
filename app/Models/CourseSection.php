<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $table = 'mdl_course_sections';

    public function courseModule(){
        return $this->hasMany(CourseModule::class, 'section');
    }

}
