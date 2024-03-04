<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Module;
use App\Models\Resource;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{

    protected $root_dir = 'pages.course.activity';

    public function create(Course $course, $activity, $section){
        $section = CourseSection::where('section', $section)->where('course', $course->id)->first(['id', 'name', 'section']);
        return view("pages.course.activity.$activity.create", compact('course', 'section'));
    }

    public function edit(Course $course, $activity, $id, $section){
        $mod = Module::where('name', $activity)->first();
        $section = CourseSection::where('section', $section)->where('course', $course->id)->first(['id', 'name', 'section']);
        switch ($activity) {
            case 'url':
                $url = Url::find($id);
                return view("pages.course.activity.$activity.edit", compact('course', 'section', 'url'));
                break;
            case 'resource':
                $resource = Resource::find($id);
                return view("pages.course.activity.file.edit", compact('course', 'section', 'resource'));
                break;
            case 'assign':
                $assignment = Assignment::find($id);
                return view("pages.course.activity.assignment.edit", compact('course', 'section', 'assignment'));
                break;
            default:
                # code...
                break;
        }
    }

    public function show(Course $course, $activity, CourseModule $courseModule){

        $section = CourseSection::find($courseModule->section);

        if($activity == 'attendance'){

            $attendance = Attendance::find($courseModule->instance);

            return view("$this->root_dir.attendance.detail", compact('course', 'section', 'attendance'));
        }
     
        
    }
}
