<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    use HasFactory;

    protected $table = 'mdl_course_modules';

    public function module(){
        return $this->belongsTo(Module::class, 'module');
    }

}
