<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAnswer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_question_answers';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
