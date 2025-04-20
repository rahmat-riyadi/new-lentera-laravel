<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAttempt extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_question_attempts';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function attemptSteps()
    {
        return $this->hasMany(QuestionAttemptStep::class, 'questionattemptid', 'id');
    }

}
