<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function destroy($shortname, $id){
        DB::beginTransaction();

        try {
            $cm = CourseModule::find($id);
            $selectedModule = Module::find($cm->module);
            $course = Course::where('shortname', $shortname)->first();
            switch ($selectedModule->name) {
                case 'url':
                    $mod_table = 'url';
                    break;
                case 'resource':
                    $mod_table = 'resource';
                    break;
                case 'attendances':
                    $mod_table = 'attendances';
                    break;
                case 'assign':
                    $mod_table = 'assignments';
                    break;
                case 'quiz':
                    $mod_table = 'quizzes';
                    break;
            }
            DB::table($mod_table)
            ->where('id', $cm->instance)
            ->delete();
            $cm->update(['deletioninprogress' => 1]);
            GlobalHelper::rebuildCourseCache($course->id);
            DB::commit();

            return response()->json([
                'message' => 'Success',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
