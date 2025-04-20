<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $connection = 'moodle_mysql';

    protected $table = 'mdl_course_sections';

    protected $guarded = ['id'];

    public function courseModule(){
        return $this->hasMany(CourseModule::class, 'section');
    }

    public $timestamps = false;

}
