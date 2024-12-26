<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSlot extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_quiz_slots';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;
}
