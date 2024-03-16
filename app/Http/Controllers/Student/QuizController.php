<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function answer(Quiz $quiz){

        $module = Module::where('name', 'quiz')->first();
        $course = Course::find($quiz->course_id);
        $courseModule = CourseModule::
        where('instance', $quiz->id)
        ->where('course', $course->id)
        ->where('module', $module->id)
        ->orderBy('added', 'DESC')
        ->first();
        $section = CourseSection::find($courseModule->section);        

        return view('pages.course.activity.quiz.answer', compact('course', 'quiz', 'courseModule', 'section'));

    }
}
