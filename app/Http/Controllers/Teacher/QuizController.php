<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    public function createQuestion(Quiz $quiz){

        $mod = Module::where('name', 'quiz')->first();

        $courseModule = CourseModule::where('instance', $quiz->id)->where('module', $mod->id)
        ->orderBy('added', 'DESC')
        ->first();

        Log::info($courseModule);

        $section = CourseSection::find($courseModule->section);
        $course = Course::find($courseModule->course);

        return view('pages.course.activity.quiz.questions', compact(
            'quiz',
            'section',
            'course',
            'courseModule'
        ));
    }
}
