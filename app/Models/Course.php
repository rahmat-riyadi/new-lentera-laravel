<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $table = 'mdl_course';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function section(){
        return $this->hasMany(CourseSection::class, 'course');
    }

    public function url(){
        return $this->hasMany(Url::class, 'course');
    }

    public function assignment(){
        return $this->hasMany(Assign::class, 'course');
    }

}
