<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingArea extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_grading_areas';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
