<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_course_modules';

    public $timestamps = false;

    public function module(){
        return $this->belongsTo(Module::class, 'module');
    }

    public function sectionDetail(){
        return $this->belongsTo(CourseSection::class, 'section');
    }

    public function course(){
        return $this->belongsTo(Course::class, 'course');
    }

}
