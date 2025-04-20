<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Course $course){
        return view('pages.course.index', compact('course'));
    }
}
