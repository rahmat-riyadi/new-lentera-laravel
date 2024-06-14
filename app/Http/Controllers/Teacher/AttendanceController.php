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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function form(Attendance $attendance){

        $mod = Module::where('name', 'attendances')->first();

        $courseModule = CourseModule::where('instance', $attendance->id)
        ->where('module', $mod->id)
        ->orderBy('added', 'DESC')
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

    public function session(Attendance $attendance){

        $mod = Module::where('name', 'attendances')->first();

        $courseModule = CourseModule::where('instance', $attendance->id)
        ->where('module', $mod->id)
        ->orderBy('added', 'DESC')
        ->first();

        $section = CourseSection::find($courseModule->section);
        $course = Course::find($courseModule->course);

        return view('pages.course.activity.attendance.session', 
        compact(
            'attendance',
            'section',
            'course',
            'courseModule'
            )
        );
    }

    public function addSession(Attendance $attendance){

        $mod = Module::where('name', 'attendances')->first();

        $courseModule = CourseModule::where('instance', $attendance->id)
        ->where('module', $mod->id)
        ->orderBy('added', 'DESC')
        ->first();

        $section = CourseSection::find($courseModule->section);
        $course = Course::find($courseModule->course);

        return view('pages.course.activity.attendance.add-session', 
        compact(
            'attendance',
            'section',
            'course',
            'courseModule'
            )
        );
    }

    public function editSession(Attendance $attendance, $session){

        $mod = Module::where('name', 'attendances')->first();

        $courseModule = CourseModule::where('instance', $attendance->id)
        ->where('module', $mod->id)
        ->orderBy('added', 'DESC')
        ->first();

        $section = CourseSection::find($courseModule->section);
        $course = Course::find($courseModule->course);

        return view('pages.course.activity.attendance.edit-session', 
        compact(
            'attendance',
            'section',
            'course',
            'courseModule',
            'session'
            )
        );

    }

}
