<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_quiz';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function questions(){
        return $this->belongsToMany(Question::class, 'quiz_questions');
    }

}
