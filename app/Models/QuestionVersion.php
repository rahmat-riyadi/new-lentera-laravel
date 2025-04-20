<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionVersion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_question_versions';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;
}
