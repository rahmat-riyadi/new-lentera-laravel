<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAttemptStep extends Model
{
    use HasFactory;

    protected $table = 'mdl_question_attempt_steps';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;
    
    public function stepData()
    {
        return $this->hasMany(QuestionAttemptStepData::class, 'attemptstepid', 'id');
    }

}
