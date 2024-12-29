<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\AssignGrade;
use App\Models\Assignment;
use App\Models\AssignmentConfig;
use App\Models\AssignmentSubmission;
use App\Models\Context;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\GradeCategory;
use App\Models\GradeGrades;
use App\Models\GradeItem;
use App\Models\GradingArea;
use App\Models\Module;
use App\Models\Resource;
use App\Models\ResourceFile;
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
                    'submission_id' => $e->assignment_submission_id,
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

    public function detailForStudent(Assignment $assignment){

        $submission = AssignmentSubmission::where('userid', auth()->user()->id)
        ->where('assignment', $assignment->id)
        ->first();

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
            $assignment->type = 'onlinetext';    
        }

        if((boolean)$file_plugin['enabled']){
            $assignment->type = 'file';    
            $assignment->max_files = $file_plugin['maxfilesubmissions'];
        }

        $assignment->formatted_duedate = Carbon::parse($assignment->duedate)->timezone('Asia/Makassar')->translatedFormat('d F Y, H:i');

        if(!$submission){
            $assignment->remaining = Carbon::parse($assignment->duedate)->diff()->format('%H Jam %i Menit');
            if(Carbon::now()->gt(Carbon::parse($assignment->duedate))){
                $assignment->remaining = 'Terlambat ' . $assignment->remaining;
            } 
            $assignment->status = 'Belum Dikumpulkan';

        }

        if($submission){

            if(Carbon::parse($assignment->duedate)->gt(Carbon::parse($submission->timemodified))){
                $assignment->status = 'Dikumpulkan';
                $assignment->remaining ='Dikumpulkan lebih awal '. Carbon::parse($assignment->duedate)->diff()->format('%H Jam %i Menit');
            } else {
                $assignment->remaining ='Terlambat '. Carbon::parse($assignment->duedate)->diff()->format('%H Jam %i Menit');
                $assignment->status = 'Terlambat Dikumpulkan';
            }

            if($assignment->type == 'file'){
                $submission->files = DB::connection('moodle_mysql')->table('mdl_files')
                // ->where('contextid', $submission->contextid)
                ->where('component', 'assignsubmission_file')
                ->where('filearea', 'submission_files')
                ->where('itemid', $submission->id)
                ->where('filename', '!=', '.')
                ->orderBy('id')
                ->get();
    
                $submission->files = $submission->files->map(function($e){
                    $e->id = $e->id;
                    $e->name = $e->filename;
                    $e->file = "/preview/file/$e->id/$e->filename";
                    $e->size = number_format($e->filesize / 1024 / 1024, 2);
                    $e->itemid = $e->itemid;
                    return $e;
                })->toArray();   
            }

            if($assignment->type == 'onlinetext'){
                $submission->url = DB::connection('moodle_mysql')->table('mdl_assignsubmission_onlinetext')
                ->where('submission', $submission->id)
                ->where('assignment', $assignment->id)
                ->first()->onlinetext;
            }

        }

        return response()->json([
            'message' => 'Success',
            'data' => [
                'assignment' => $assignment,
                'submission' => $submission,
            ]
        ], 200);

    }

    public function submitSubmission(Request $request, Assignment $assignment){

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
            $submission_type = 'onlinetext';
        } 

        if($file_plugin){
            $submission_type = 'file';
            $submission_file_number = $assignment->configs()
            ->where('subtype', 'assignsubmission')
            ->where('plugin', 'file')
            ->where('name', 'maxfilesubmissions')
            ->first('value')->value;
        }

        $submission = AssignmentSubmission::where('userid', auth()->user()->id)
        ->where('assignment', $assignment->id)
        ->orderBy('id')
        ->first();

        DB::connection('moodle_mysql')->beginTransaction();

        try {

            DB::connection('moodle_mysql')->table('mdl_assign_submission')
            ->updateOrInsert(
                [
                    'assignment' => $assignment->id,
                    'userid' => auth()->user()->id,
                ],[
                    'assignment' => $assignment->id, 
                    'userid' => auth()->user()->id,
                    'status' => 'submitted',
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'groupid' => 0,
                    'attemptnumber' => 0,
                    'latest' => 1,
                    'timestarted' => null,
                ]
            );

            $submission = AssignmentSubmission::where('userid', auth()->user()->id)
            ->where('assignment', $assignment->id)->first();

            DB::connection('moodle_mysql')->table('mdl_assignsubmission_file')
            ->updateOrInsert(
                [
                    'assignment' => $assignment->id,
                    'submission' => $submission->id
                ],[
                    'numfiles' => count($request->files),
                ]
            );

            $mod_id = Module::where('name', 'assign')->first('id');

            $cm = CourseModule::where("module", $mod_id->id)
            ->where('instance', $assignment->id)
            ->first();

            $context = Context::where('instanceid', $cm->id)
            ->where('contextlevel', 70)
            ->first();

            if($submission_type == 'file'){
                foreach($request->get('files') as $i => $files){

                    if(!isset($files['itemid'])){
                        continue;
                    }

                    $user_ctx = Context::where('instanceid', $request->user()->id)
                    ->where('contextlevel', 30)
                    ->first();

                    $get_files = ResourceFile::where('component', 'user')
                    ->where('filearea', 'draft')
                    ->where('contextid', $user_ctx->id)
                    ->where('itemid', $files['itemid'])
                    ->get();

                    ResourceFile::create([
                        'contenthash' => $get_files[0]->contenthash,
                        'pathnamehash' => GlobalHelper::get_pathname_hash($get_files[0]->contextid, 'assignsubmission_file', 'submission_files', $get_files[0]->itemid, '/', $get_files[0]->filename),
                        'contextid' => $context->id,
                        'component' => 'assignsubmission_file',
                        'filearea' => 'submission_files',
                        'itemid' => $submission->id,
                        'filepath' => '/',
                        'filename' => $get_files[0]->filename,
                        'userid' => $request->user()->id,
                        'filesize' => $get_files[0]->filesize,
                        'mimetype' => $get_files[0]->mimetype,
                        'status' => 0,
                        'source' => $get_files[0]->filename,
                        'author' => $get_files[0]->author,
                        'license' => $get_files[0]->license,
                        'timecreated' => $get_files[0]->timecreated,
                        'timemodified' => $get_files[0]->timemodified,
                        'sortorder' => 1,
                        'referencefileid' => null,
                    ]);

                    ResourceFile::create([
                        'contenthash' => $get_files[1]->contenthash,
                        'pathnamehash' => GlobalHelper::get_pathname_hash($get_files[1]->contextid,'assignsubmission_file', 'submission_files', $get_files[1]->itemid, '/', $get_files[1]->filename),
                        'contextid' => $context->id,
                        'component' => 'assignsubmission_file',
                        'filearea' => 'submission_files',
                        'itemid' => $submission->id,
                        'filepath' => '/',
                        'filename' => $get_files[1]->filename,
                        'userid' => $request->user()->id,
                        'filesize' => $get_files[1]->filesize,
                        'mimetype' => $get_files[1]->mimetype,
                        'timecreated' => $get_files[1]->timecreated,
                        'timemodified' => time(),
                    ]);
                    
                }   
            }

            if($submission_type == 'onlinetext'){
                DB::connection('moodle_mysql')->table('mdl_assignsubmission_onlinetext')
                ->updateOrInsert(
                    [
                        'assignment' => $assignment->id,
                        'submission' => $submission->id
                    ],[
                        'onlinetext' => $request->onlinetext,
                    ]
                );
            }

            DB::connection('moodle_mysql')->commit();

            if($submission_type == 'file'){

                $newFiles = DB::connection('moodle_mysql')->table('mdl_files')
                ->where('component', 'assignsubmission_file')
                ->where('filearea', 'submission_files')
                ->where('itemid', $submission->id)
                ->where('filename', '!=', '.')
                ->orderBy('id')
                ->get();

                $newFiles = $newFiles->map(function($e){
                    $e->id = $e->id;
                    $e->name = $e->filename;
                    $e->file = "/preview/file/$e->id/$e->filename";
                    $e->size = number_format($e->filesize / 1024 / 1024, 2);
                    $e->itemid = $e->itemid;
                    return $e;
                })->toArray(); 
            }

            return response()->json([
                'message' => 'Success',
                'data' => [
                    'files' => $newFiles ?? null,
                    'url' => $request->onlinetext ?? null
                ]
            ], 200);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
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

            GlobalHelper::rebuildCourseCache($course->id);

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

    public function getDetailGrading(Request $request,AssignmentSubmission $assignmentSubmission){
        $module = Module::where('name', 'assign')->first();
        $assignment = Assignment::find($assignmentSubmission->assignment);
        $user = User::find($assignmentSubmission->userid);

        $courseModule = CourseModule::
        where('instance', $assignment->id)
        ->where('course', $assignment->course)
        ->where('module', $module->id)
        ->orderBy('added', 'DESC')
        ->select('id')
        ->first();

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
            $type = 'onlinetext';
        } 

        if($file_plugin){
            $type = 'file';
            $ctx_id = Context::where('contextlevel', 70)->where('instanceid', $courseModule->id)->first('id')->id;
    
            $files = DB::connection('moodle_mysql')->table('mdl_files')
            ->where('contextid', $ctx_id)
            ->where('component', 'assignsubmission_file')
            ->where('filearea', 'submission_files')
            ->where('itemid', $assignmentSubmission->id)
            ->where('filename', '!=', '.')
            ->orderBy('id')
            ->get();
    
            $files = $files->map(function($e){
                $e->id = $e->id;
                $e->name = $e->filename;
                $e->file = "/preview/file/$e->id/$e->filename";
                $e->size = number_format($e->filesize / 1024 / 1024, 2) . ' MB';
                $e->itemid = $e->itemid;
                return $e;
            })->toArray();   
        }

        if(is_null($assignmentSubmission->timemodified) || $assignmentSubmission->status == 'new'){
            $status = 'Belum Dikumpulkan';
        } else {
            $sub_time = Carbon::parse($assignmentSubmission->timemodified);
            $assign_time = Carbon::parse($assignment->duedate);
            if($sub_time->gt($assign_time)){
                $status = 'Terlambat Dikumpulkan';
            } else {
                $status = 'Dikumpulkan';
            }
        }

        $grade = AssignGrade::where('assignment', $assignment->id)
        ->where('userid', $user->id)->first('grade')->grade ?? 0;    

        $data = [
            'student' => [
                'name' => $user->firstname . ' ' . $user->lastname,
                'id' => $user->id,
                'nim' => $user->username,
            ],
            'assignment' => [
                'name' => $assignment->name,
                'type' => $type,
                'files' => $files,
                'grade' => $grade,
                'status' => $status,
            ]
        ];

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ], 200);

    }

    public function deleteFileSubmission($id){

        DB::connection('moodle_mysql')->beginTransaction();

        try {

            $file = ResourceFile::where('id', $id)->first();

            $draftFile = ResourceFile::where('contenthash', $file->contenthash)
            ->where('component', 'user')
            ->where('filearea', 'draft')
            ->first();

            $itemId = $draftFile->itemid;

            ResourceFile::where('itemid', $itemId)
            ->where('component', 'user')
            ->where('filearea', 'draft')
            ->delete();

            ResourceFile::where('id', $id+1)->delete();
            $file->delete();

            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'Success',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
        
    }

}
