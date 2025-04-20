<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_attendance_statuses';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

}
