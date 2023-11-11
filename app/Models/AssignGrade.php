<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignGrade extends Model
{
    use HasFactory;

    protected $table = 'mdl_assign_grades';

    protected $guarded = ['id'];

}
