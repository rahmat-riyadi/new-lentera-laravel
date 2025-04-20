<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionTrueFalse extends Model
{
    use HasFactory;

    protected $table = 'mdl_question_truefalse';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;
}
