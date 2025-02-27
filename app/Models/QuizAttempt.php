<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_quiz_attempts';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function questionUsage()
    {
        return $this->belongsTo(QuestionUsage::class, 'uniqueid', 'id');
    }
}
