<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function create(Course $course, $activity, $section){
        $section = CourseSection::where('section', $section)->where('course', $course->id)->first(['id', 'name', 'section']);
        return view('pages.course.activity.url.create', compact('course', 'section'));
    }
}
