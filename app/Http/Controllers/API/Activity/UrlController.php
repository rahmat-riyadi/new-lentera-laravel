<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UrlController extends Controller
{

    public function findById(Url $url){
        return response()->json([
            'message' => 'Success',
            'data' => $url
        ], 200);
    }

    public function store(Request $request, $shortname){

        $module = Module::where('name', 'url')->first();
        $course = Course::where('shortname', $shortname)->first();

        DB::connection('moodle_mysql')->beginTransaction();

        try {
            $instance = $course->url()->create([
                'name' => $request->name,
                'intro' => $request->description,
                'externalurl' => $request->url,
            ]);
            $cm = CourseHelper::addCourseModule($course->id, $module->id, $instance->id);
            CourseHelper::addContext($cm->id, $course->id);
            CourseHelper::addCourseModuleToSection($course->id, $cm->id, $request->section);
            DB::connection('moodle_mysql')->commit();
            GlobalHelper::rebuildCourseCache($course->id);

            return response()->json([
                'message' => 'Success'
            ], 200);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function update(Request $request, Url $url){

        try {

            $url->update([
                'name' => $request->name,
                'intro' => $request->description,
                'externalurl' => $request->url,
            ]);
    
            GlobalHelper::rebuildCourseCache($url->course);

            return response()->json([
                'message' => 'Success',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

    }
}
