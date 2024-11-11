<?php

namespace App\Http\Controllers\API;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class CourseController extends Controller
{
    public function getAllCourse(Request $request){
        $time = time();
        $course = Course::
        whereIn('mdl_course.id', function($q) use ($time, $request){
            $q->select('e.courseid')
            ->from('mdl_enrol as e')
            ->join('mdl_user_enrolments as ue', function ($join) use ($request) {
                $join->on('ue.enrolid', '=', 'e.id')
                    ->where('ue.userid', '=', $request->user()->id);
            })
            ->join('mdl_course as c', 'c.id', '=', 'e.courseid')
            ->where('ue.status', '=', '0')
            ->where('e.status', '=', '0')
            ->where('ue.timestart', '<=', $time)
            ->where(function ($query) use ($time) {
                $query->where('ue.timeend', '=', 0)
                        ->orWhere('ue.timeend', '>', $time);
            });
        })
        ->select(
            'mdl_course.id',
            'mdl_course.fullname',
            'mdl_course.shortname',
        )
        ->get();

        $courses = $course;
        $courses = $courses->filter(function($e)  {
            return !($e->startdate > time()) && !($e->enddate < time() && $e->enddate != 0);
        });

        return response()->json([
            'message' => 'Success',
            'data' => [
                'courses' => $courses
            ]
        ], 200);

    }

    public function changeTopic (Request $request, $shortname, $section) {

        try {
            $course = Course::where('shortname', $shortname)->first();
            CourseSection::where('id',$section)->update([
                'name' => $request->name,
                'timemodified' => time()
            ]);
            GlobalHelper::rebuildCourseCache($course->id);       

            return response()->json([
                'message' => 'Success'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function getTopic(Request $request, $shortname){

        $sections = [];
        $course = Course::where('shortname', $shortname)->first();
        $courseSections = CourseSection::where('course', $course->id)->get();

        foreach($courseSections as $cs){

            $section = new stdClass();
    
            $section->id = $cs->id;
            $section->name = $cs->name;
            $section->section = $cs->section;
            $section->modules = [];
    
            if(!empty($cs->sequence)){
    
                $cmids = explode(',', $cs->sequence);
    
                $courseModules = CourseModule::whereIn('id', $cmids)
                ->where('deletioninprogress', 0)
                ->where('course', $course->id)
                ->get();
    
                foreach($courseModules as $cm){
    
                    $module = new stdClass();
    
                    $module->id = $cm->id;
                    $module->instance = $cm->instance;
                    $module->module = $cm->module;
    
                    $selectedModule = Module::find($cm->module);
    
                    switch ($selectedModule->name) {
                        case 'url':
                            $mod_table = 'mdl_url';
                            break;
                        case 'resource':
                            $mod_table = 'mdl_resource';
                            break;
                        case 'attendance':
                            $mod_table = 'mdl_attendance';
                            break;
                        case 'assign':
                            $mod_table = 'mdl_assign';
                            break;
                        case 'quiz':
                            $mod_table = 'mdl_quiz';
                            break;
                    }
    
                    if(!empty($mod_table)){
                        $instance = DB::connection('moodle_mysql')->table($mod_table)
                        ->where('id', $cm->instance)
                        ->first();
                    }
    
    
                    $module->name = $instance->name ?? '';
                    $module->description = $instance->intro ?? '';
                    $module->modname = $selectedModule->name;
    
                    if($selectedModule->name == 'url'){
                        $module->url = $instance->externalurl ?? '';   
                    }
    
                    if($selectedModule->name == 'assign'){
                        
                    }
    
                    if($selectedModule->name == 'resource'){
    
                        $file_ctx = DB::connection('moodle_mysql')->table('mdl_context')
                        ->where('instanceid', $cm->id)
                        ->where('contextlevel', 70)
                        ->first('id');
    
                        $files = DB::connection('moodle_mysql')->table('mdl_files')
                        ->where('contextid', $file_ctx->id)
                        ->where('component', 'mod_resource')
                        ->whereNotNull('mimetype')
                        ->whereNotNull('source')
                        ->orderBy('id')
                        ->get();
    
                        $module->file = $files->map(function($e){

                            $fileFormatted = new stdClass();
    
                            $ext = explode('.',$e->filename);
                            $ext = $ext[count($ext)-1];

                            $fileFormatted->name = $e->filename;
                            $fileFormatted->file = url("/api/preview/file/$e->id/$e->filename");
    
                            return $fileFormatted;
                        });   
                    }
    
                    $section->modules[] = $module;
                }
            }

            $sections[] = $section;
    
        }

        return response()->json([
            'message' => 'Success',
            'data' => [
                'topics' => $sections
            ]
        ], 200);

    }
}
