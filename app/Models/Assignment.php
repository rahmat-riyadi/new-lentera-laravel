<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_assign';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function configs(){
        return $this->hasMany(AssignmentConfig::class, 'assignment');
    }

    public function files(){
        return $this->hasMany(AssignmentFile::class);
    }

}
