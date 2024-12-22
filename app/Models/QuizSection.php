<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSection extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_quiz_sections';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
