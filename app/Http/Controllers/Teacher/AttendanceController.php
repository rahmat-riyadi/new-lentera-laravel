<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Module;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function form(Attendance $attendance){

        $mod = Module::where('name', 'attendance')->first();

        $courseModule = CourseModule::where('instance', $attendance->id)->where('module', $mod->id)
        ->first();

        $section = CourseSection::find($courseModule->section);
        $course = Course::find($courseModule->course);

        return view(
            'pages.course.activity.attendance.form',
            compact(
                'attendance',
                'section',
                'course',
                'courseModule'
            )
        );
    }

}
