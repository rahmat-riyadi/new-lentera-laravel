<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionUsage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_question_usages';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function questionAttempts()
    {
        return $this->hasMany(QuestionAttempt::class, 'questionusageid', 'id');
    }

}
