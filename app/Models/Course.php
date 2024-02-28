<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $connection = 'moodle_mysql';

    protected $table = 'mdl_course';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function section(){
        return $this->hasMany(CourseSection::class, 'course');
    }

    public function url(){
        return $this->setConnection('mysql')->hasMany(Url::class, 'course_id');
    }

    public function assignment(){
        return $this->hasMany(Assign::class, 'course');
    }

    public function quiz(){
        return $this->hasMany(Assign::class, 'course');
    }

    public function resource(){
        return $this->hasMany(Resource::class);
    }

}
