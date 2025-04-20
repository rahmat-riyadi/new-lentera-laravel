<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    use HasFactory;

    protected $connection = 'moodle_mysql';

    protected $guarded = ['id'];

    protected $table = 'mdl_url';

    public $timestamps = false;
}
