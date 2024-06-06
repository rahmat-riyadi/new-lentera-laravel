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

    public function categoryInfo(){
        return $this->belongsTo(CourseCategory::class, 'category');
    }

    public function section(){
        return $this->hasMany(CourseSection::class, 'course');
    }

    public function url(){
        return $this->setConnection('mysql')->hasMany(Url::class, 'course');
    }

    public function assignment(){
        return $this->hasMany(Assignment::class, 'course');
    }

    public function quiz(){
        return $this->setConnection('mysql')->hasMany(Quiz::class);
    }

    public function resource(){
        return $this->setConnection('mysql')->hasMany(Resource::class);
    }

    public function attendance(){
        return $this->setConnection('mysql')->hasMany(Attendance::class);
    }

}
