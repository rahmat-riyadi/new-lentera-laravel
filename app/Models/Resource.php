<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $table = 'mdl_resource';

    protected $guarded = ['id'];

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function files(){
        return $this->hasMany(ResourceFile::class);
    }
}
