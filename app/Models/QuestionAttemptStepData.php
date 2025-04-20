<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAttemptStepData extends Model
{
    use HasFactory;

    protected $table = 'mdl_question_attempt_step_data';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
