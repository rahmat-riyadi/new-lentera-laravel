<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    public function show(Assignment $assignment, AssignmentSubmission $assignmentSubmission){

        $module = Module::where('name', 'assign')->first();

        $course = Course::find($assignment->course_id);
        $courseModule = CourseModule::
        where('instance', $assignment->id)
        ->where('course', $course->id)
        ->where('module', $module->id)
        ->orderBy('added', 'DESC')
        ->first();
        $section = CourseSection::find($courseModule->section);

        return view('pages.course.activity.assignment.grading', compact('assignment', 'assignmentSubmission', 'course', 'section', 'courseModule'));

    }

    public function grade(Assignment $assignment, AssignmentSubmission $assignmentSubmission){
        $module = Module::where('name', 'assign')->first();
        $course = Course::find($assignment->course);
        $courseModule = CourseModule::
        where('instance', $assignment->id)
        ->where('course', $course->id)
        ->where('module', $module->id)
        ->orderBy('added', 'DESC')
        ->first();
        $section = CourseSection::find($courseModule->section);
        $student = User::find($assignmentSubmission->userid);
        return view('pages.course.activity.assignment.grading', compact('assignment', 'course', 'section', 'courseModule', 'student', 'assignmentSubmission', 'courseModule'));
    }

    public function createSubmission(Assignment $assignment){
        $module = Module::where('name', 'assign')->first();
        $course = Course::find($assignment->course);
        $courseModule = CourseModule::
        where('instance', $assignment->id)
        ->where('course', $course->id)
        ->where('module', $module->id)
        ->orderBy('added', 'DESC')
        ->first();
        $section = CourseSection::find($courseModule->section);

        return view('pages.course.activity.assignment.submit-submission', compact('assignment', 'course', 'section', 'courseModule'));   

    }
}
