<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentConfig;
use App\Models\Context;
use App\Models\Course;
use App\Models\GradeCategory;
use App\Models\GradeGrades;
use App\Models\GradeItem;
use App\Models\GradingArea;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class AssignmentController extends Controller
{

    public function findById(Assignment $assignment){
        
        $instance = new stdClass();

        $instance->id = $assignment->id;
        $instance->name = $assignment->name;
        $instance->description = $assignment->intro;

        $start = Carbon::parse($assignment->allowsubmissionsfromdate)->timezone('Asia/Makassar');
        $end = Carbon::parse($assignment->duedate)->timezone('Asia/Makassar');

        $instance->start_date = $start->translatedFormat('Y-m-d');
        $instance->end_date = $end->translatedFormat('Y-m-d');

        $instance->start_time = $start->translatedFormat('H:i');
        $instance->end_time = $end->translatedFormat('H:i');

        if($start->diffInDays($end) == 0){
            $instance->time_type = 'time';
        } else {
            $instance->time_type = 'date';
        }

        $online_text_plugin = $assignment->configs()
        ->where('subtype', 'assignsubmission')
        ->where('plugin', 'onlinetext')
        ->get();

        $file_plugin = $assignment->configs()
        ->where('subtype', 'assignsubmission')
        ->where('plugin', 'file')
        ->get();

        $online_text_plugin = $online_text_plugin->mapWithKeys(function($plugin){
            return [  $plugin['name'] => $plugin['value'] ];
        })->toArray();

        $file_plugin = $file_plugin->mapWithKeys(function($plugin){
            return [  $plugin['name'] => $plugin['value'] ];
        })->toArray();

        if((boolean)$online_text_plugin['enabled']){
            $instance->submission_type = 'onlinetext';    
            $instance->word_limit = $online_text_plugin['wordlimit'];
        }

        if((boolean)$file_plugin['enabled']){
            $instance->submission_type = 'file';    
            $instance->file_max_size = $file_plugin['maxsubmissionsizebytes'];    
            $instance->file_types = $file_plugin['filetypeslist'];
            $instance->file_amount = $file_plugin['maxfilesubmissions'];
        }

        return response()->json([
            'message' => 'Success',
            'data' => $instance
        ], 200);

    }

    public function detail(Request $request,$shortname, Assignment $assignment){

        $course = Course::where('shortname', $shortname)->first();
        $role = Role::where('shortname', 'student')->first();

        try {
            
            $instance = new stdClass();

            $due_date = Carbon::parse($assignment->duedate)->timezone('Asia/Makassar');

            $instance->id = $assignment->id;
            $instance->name = $assignment->name;
            $instance->due_date = $due_date->translatedFormat('d F Y, H:i');
            $instance->due_date_formatted = $due_date->diffForHumans(['parts' => 2]);

            $online_text_plugin = $assignment->configs()
                ->where('subtype', 'assignsubmission')
                ->where('plugin', 'onlinetext')
                ->where('name', 'enabled')
                ->where('value', 1)
                ->first();

            $file_plugin = $assignment->configs()
                ->where('subtype', 'assignsubmission')
                ->where('plugin', 'file')
                ->where('name', 'enabled')
                ->where('value', 1)
                ->first();

            if($online_text_plugin){
                $instance->type = 'onlinetext';
            } 

            if($file_plugin){
                $instance->type = 'file';
            }

            $studentIds = DB::connection('moodle_mysql')->table('mdl_enrol')
                ->where('mdl_enrol.courseid', $course->id)
                ->where('mdl_enrol.roleid', $role->id)
                ->where('mdl_user_enrolments.userid', '!=',$request->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                ->pluck('mdl_user_enrolments.userid');

            Log::info($studentIds);

            $students = User::query()
            ->whereIn('mdl_user.id', $studentIds)
            ->leftJoin('mdl_assign_submission as s', function($q) use ($assignment) {
                $q->on('s.userid', '=', 'mdl_user.id')
                ->where('s.assignment', $assignment->id);
            })
            ->select(
                'mdl_user.id',
                's.id as assignment_submission_id',
                DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
                'mdl_user.username as nim',
                's.timecreated',
                's.timemodified',
                's.status',
            )
            ->get();

            Log::info($students);

            $instance->students = $students->map(function ($e) use ($assignment) {

                // Log::info($e);

                if(!empty($e->timemodified) && $e->status != 'new'){
                    $submit_time = Carbon::parse($e->timemodified)->timezone('Asia/Makassar')->translatedFormat('d F Y, H:i');
                } else {
                    $submit_time = '-';
                }

                if(is_null($e->timemodified) || $e->status == 'new'){
                    $status = 'Belum Dikumpulkan';
                } else {
                    $sub_time = Carbon::parse($e->timemodified);
                    $assign_time = Carbon::parse($assignment->duedate);
                    if($sub_time->gt($assign_time)){
                        $status = 'Terlambat Dikumpulkan';
                    } else {
                        $status = 'Dikumpulkan';
                    }
                }

                return [
                    'id' => $e->id,
                    'fullname' => $e->fullname,
                    'nim' => $e->nim,
                    'status' => $status,
                    'submit_time' => $submit_time
                ];
            });

            $instance->submitted_count = DB::connection('moodle_mysql')
            ->table('mdl_assign_submission as s')
            ->where('s.latest', 1)
            ->where('s.assignment', $assignment->id)
            ->whereNotNull('s.timemodified')
            ->where('s.status', 'submitted')
            ->whereIn('s.userid', $studentIds)
            ->count('s.userid');

            $instance->need_grading_count = DB::connection('moodle_mysql')
            ->table('mdl_assign_submission as s')
            ->leftJoin('mdl_assign_grades as g', function($q){
                $q->on('s.assignment', '=', 'g.assignment')
                ->on('s.userid', '=', 'g.userid')
                ->on('s.attemptnumber', '=', 'g.attemptnumber');
            })
            ->whereIn('s.userid', $studentIds)
            ->where('s.latest', 1)
            ->where('s.assignment', $assignment->id)
            ->where('s.status', 'submitted')
            ->whereNotNull('s.timemodified')
            ->where(function ($query) {
                $query->where('s.timemodified', '>=', DB::raw('g.timemodified'))
                    ->orWhereNull('g.timemodified')
                    ->orWhereNull('g.grade');
            })
            ->count('s.userid');

            return response()->json([
                'message' => 'Success',
                'data' => $instance
            ], 200);
            

            
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
        
    }

    public function store(Request $request, $shortname){

        $module = Module::where('name', 'assign')->first();
        $course = Course::where('shortname', $shortname)->first();

        $start_date = Carbon::parse($request->start_date . ' '. $request->start_time );
        $due_date = Carbon::parse($request->end_date . ' '. $request->end_time );

        DB::beginTransaction();
        
        try {

            $instance = Assignment::create([
                'course' => $course->id,
                'name' => $request->name,
                'intro' => $request->description,
                'allowsubmissionsfromdate' => $start_date->unix(),
                'duedate' => $due_date->unix(),
                'grade' => 100,
                'introformat' => 1,
                'alwaysshowdescription' => 1,
            ]);

            if($request->submission_type == 'onlinetext'){
                $instance->configs()->createMany([
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 0,
                    ],
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'wordlimitenabled',
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'wordlimit',
                        'value' => $request->word_limit,
                    ],
                ]);
            }

            if($request->submission_type == 'file'){
                $instance->configs()->createMany([
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 0,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'maxfilesubmissions',
                        'value' => $request->file_amount ?? 1,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'maxsubmissionsizebytes',
                        'value' => $request->file_max_size,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'filetypeslist',
                        'value' => $request->file_types ?? '*',
                    ],
                ]);
            }

            $GradeCategory = GradeCategory::firstWhere("courseid", $course->id);
            $gradeItem = GradeItem::create([
                'courseid' => $course->id,
                'categoryid' => $GradeCategory->id,
                'itemname' => $request->name,
                'itemtype' => 'mod',
                'itemnumber' => 0,
                'itemmodule' => 'assign',
                'iteminstance' => $instance->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
            
            $context = Context::where('instanceid', $course->id)
            ->where('contextlevel', 50)
            ->first();

            $participantsData = DB::connection('moodle_mysql')
            ->table('mdl_role_assignments as ra')
            ->where('contextid', $context->id)
            ->join('mdl_user as u', 'u.id', '=', 'ra.userid')
            ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
            ->where('r.shortname', '!=', 'editingteacher')
            ->select(
                'u.id',
            )->get();

            $grade_grades_data = $participantsData->map(function ($participant) use ($gradeItem) {
                return [
                    'userid' => $participant->id,
                    'itemid' => $gradeItem->id,
                ];
            });

            GradeGrades::insert($grade_grades_data->toArray());

            $cm = CourseHelper::addCourseModule($course->id, $module->id, $instance->id);
            $ctx = CourseHelper::addContext($cm->id, $course->id);
            CourseHelper::addCourseModuleToSection($course->id, $cm->id, $request->section);

            GradingArea::create([
                'contextid' => $ctx->id,
                'component' => 'mod_assign',
                'areaname' => 'submissions',
            ]);

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

    public function update(Request $request, Assignment $assignment){

        $start_date = Carbon::parse($request->start_date . ' '. $request->start_time );
        $due_date = Carbon::parse($request->end_date . ' '. $request->end_time );

        DB::beginTransaction();

        try {
            //code...
            $assignment->update([
                'name' => $request->name,
                'intro' => $request->description,
                'allowsubmissionsfromdate' => $start_date->unix(),
                'duedate' => $due_date->unix(),
            ]);

            if($request->submission_type == 'onlinetext'){
                AssignmentConfig::where('assignment', $assignment->id)
                ->where('plugin', 'file')
                ->where('subtype', 'assignsubmission')
                ->where('name', 'enabled')
                ->update([
                    'value' => 0
                ]);
                $assignment->configs()->createMany([
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 0,
                    ],
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'wordlimitenabled',
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'wordlimit',
                        'value' => $request->word_limit,
                    ],
                ]);
            }

            if($request->submission_type == 'file'){
                AssignmentConfig::where('assignment', $assignment->id)
                ->where('plugin', 'onlinetext')
                ->where('subtype', 'assignsubmission')
                ->where('name', 'enabled')
                ->update([
                    'value' => 0
                ]);
                $assignment->configs()->createMany([
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'onlinetext',
                        'subtype' => 'assignsubmission',
                        'name' => 'enabled',
                        'value' => 0,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'maxfilesubmissions',
                        'value' => $request->file_amount ?? 1,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'maxsubmissionsizebytes',
                        'value' => $request->file_max_size,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'filetypeslist',
                        'value' => $request->file_types ?? '*',
                    ],
                ]);
            }

            DB::commit();

            GlobalHelper::rebuildCourseCache($assignment->course);

            return response()->json([
                'message' => 'Success',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

    }


}
