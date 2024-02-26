<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Context extends Model
{
    use HasFactory;

    protected $connection = 'moodle_mysql';

    protected $table = 'mdl_context';

    protected $guarded = ['id'];

    public $timestamps = false;
}
