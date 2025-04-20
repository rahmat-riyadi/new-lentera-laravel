<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBankEntry extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_question_bank_entries';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}   
