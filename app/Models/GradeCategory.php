<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_grade_categories';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
