<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrol extends Model
{
    use HasFactory;

    protected $table = 'mdl_enrol';

    public function course(){
        return $this->belongsTo(Course::class, 'courseid');
    }

}
