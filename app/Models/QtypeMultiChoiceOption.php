<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtypeMultiChoiceOption extends Model
{
    use HasFactory;

    protected $table = 'mdl_qtype_multichoice_options';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
