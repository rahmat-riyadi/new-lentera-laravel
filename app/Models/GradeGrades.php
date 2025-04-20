<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeGrades extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_grade_grades';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;
}
