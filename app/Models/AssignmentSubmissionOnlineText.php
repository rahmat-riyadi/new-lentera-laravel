<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmissionOnlineText extends Model
{
    use HasFactory;

    protected $connection = 'moodle_mysql';

    protected $guarded = ['id'];

    protected $table = 'mdl_assignsubmission_onlinetext';

    public $timestamps = false;    

}
