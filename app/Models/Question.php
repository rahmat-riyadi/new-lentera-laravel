<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_question';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function answers(){
        return $this->hasMany(Answer::class);
    }

}
