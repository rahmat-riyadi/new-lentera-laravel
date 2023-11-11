<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignSubmission extends Model
{
    use HasFactory;

    protected $table = 'mdl_assign_submission';

    protected $guarded = ['id'];

}
