<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    use HasFactory;

    protected $connection = 'moodle_mysql';

    protected $table = 'mdl_course_categories';

    protected $guarded = ['id'];

    public $timestamps = false;
}
