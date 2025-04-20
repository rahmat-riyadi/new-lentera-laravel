<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceFile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_files';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;


}
