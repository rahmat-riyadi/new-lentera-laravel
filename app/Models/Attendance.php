<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_attendance';

    protected $connection = 'moodle_mysql';

    public $timestamps = false;

    public function statuses(){
        return $this->hasMany(AttendanceStatus::class, 'attendanceid');
    }
}
