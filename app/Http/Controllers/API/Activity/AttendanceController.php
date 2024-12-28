<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Context;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Event;
use App\Models\GradeItem;
use App\Models\Module;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{

    public function findById(Attendance $attendance){

        $cm = CourseModule::where('instance', $attendance->id)
        ->where('module', Module::where('name', 'quiz')->first()->id)
        ->where('course', $attendance->course)
        ->first();

        $cs = CourseSection::where('id', $cm->section)->first(['section', 'name']);

        $attendance->course_section = $cs;

        return response()->json([
            'message' => 'Success',
            'data' => $attendance
        ], 200);
    }

    public function store(Request $request, $shortname){

        $module = Module::where('name', 'attendance')->first();

        $course = Course::where('shortname', $shortname)->first();

        DB::connection('moodle_mysql')->beginTransaction();

        try {

            $instance = Attendance::create([
                'course' => $course->id,
                'name' => $request->name,
                'intro' => $request->description,
                'grade' => 100,
                'timemodified' => time(),
                'introformat' => 1,
                'sessiondetailspos' => 'left',
                'showsessiondetails' => 1,
                'showextrauserdetails' => 1,
            ]);

            $status = [
                [
                    'attendanceid' => $instance->id,
                    'acronym' => 'P',
                    'description' => 'Present',
                    'grade' => 2.00,
                ],
                [
                    'attendanceid' => $instance->id,
                    'acronym' => 'A',
                    'description' => 'Absent',
                    'grade' => 0.00,
                ],
                [
                    'attendanceid' => $instance->id,
                    'acronym' => 'L',
                    'description' => 'Late',
                    'grade' => 1.00,
                ],
                [
                    'attendanceid' => $instance->id,
                    'acronym' => 'E',
                    'description' => 'Excused',
                    'grade' => 1.00,
                ],
                [
                    'attendanceid' => $instance->id,
                    'acronym' => 'S',
                    'description' => 'Sick',
                    'grade' => 1.00,
                ],
            ];

            $instance->statuses()->createMany($status);

            $gradeItem = GradeItem::where('courseid', $course->id)->max('sortorder');

            GradeItem::create([
                'courseid' => $course->id,
                'categoryid' => $course->id,
                'itemname' => $request->name,
                'itemtype' => 'mod',
                'itemmodule' => 'attendance',
                'iteminstance' => $instance->id,
                'itemnumber' => 0,
                'iteminstance' => $instance->id,
                'gradetype' => 1,
                'grademax' => 100,
                'grademin' => 0,
                'scaleid' => null,
                'outcomeid' => null,
                'gradepass' => 0,
                'multfactor' => 1,
                'plusfactor' => 0,
                'aggregationcoef' => 0,
                'aggregationcoef2' => 0,
                'sortorder' => $gradeItem + 1,
                'display' => 0,
                'decimals' => null,
                'hidden' => 0,
                'locked' => 0,
                'locktime' => 0,
                'needsupdate' => 1,
                'weightoverride' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $cm = CourseHelper::addCourseModule($course->id, $module->id, $instance->id);
            CourseHelper::addContext($cm->id, $course->id);
            CourseHelper::addCourseModuleToSection($course->id, $cm->id, $request->section);

            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'Attendance created successfully'
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function update(Request $request, Attendance $attendance){

        DB::connection('moodle_mysql')->beginTransaction();

        try {

            $attendance->update([
                'name' => $request->name,
                'intro' => $request->description,
                'timemodified' => time(),
            ]);

            DB::connection('moodle_mysql')->commit();

            GlobalHelper::rebuildCourseCache($attendance->id);

            return response()->json([
                'message' => 'Attendance updated successfully'
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function getSession(Attendance $attendance){

        $sessions = DB::connection('moodle_mysql')
        ->table('mdl_attendance_sessions')
        ->where('attendanceid', $attendance->id)
        ->get();

        foreach ($sessions as $session) {
            $session->non_format = Carbon::parse($session->sessdate)->setTimezone('Asia/Makassar')->format('Y-m-d');
            $session->date = Carbon::parse($session->sessdate)->setTimezone('Asia/Makassar')->translatedFormat('l, d F Y');
            $session->time_start = Carbon::parse($session->sessdate)->setTimezone('Asia/Makassar')->translatedFormat('H:i');
            $session->time_end = Carbon::parse($session->sessdate)->setTimezone('Asia/Makassar')->addSeconds($session->duration)->translatedFormat('H:i');
        }

        return response()->json([
            'message' => 'Success',
            'data' => $sessions
        ], 200);

    }

    public function addSession(Request $request, Attendance $attendance){

        DB::connection('moodle_mysql')->beginTransaction();
        
        try {
            //code...
            
            $date = Carbon::parse($request->date. ' '. $request->time_start);

            $duration = 0;

            if(isset($request->time_end)){
                $time_end = Carbon::parse($request->date. ' '. $request->time_end);
                $duration = $time_end->setTimezone('Asia/Makassar')->unix() - $date->setTimezone('Asia/Makassar')->unix();
            }

            $id = DB::connection('moodle_mysql')
            ->table('mdl_attendance_sessions')
            ->insertGetId([
                'attendanceid' => $attendance->id,
                'sessdate' => $date->setTimezone('Asia/Makassar')->unix(),
                'duration' => $duration,
                'timemodified' => time(),
                'studentscanmark' => $request->studentscanmark,
                'allowupdatestatus' => 0,
                'description' => '',
                'descriptionformat' => 1,
                'autoassignstatus' => 0,
                'studentpassword' => '',
                'automark' => 0,
                'automarkcompleted' => 0,
                'automarkcmid' => 0,
                'absenteereport' => 1,
                'includeqrcode' => 0,
                'rotateqrcode' => 0,
                'rotateqrcodesecret' => '',
            ]);

            $event = Event::create([
                'name' => $attendance->name,
                'description' => '',
                'format' => 1,
                'categoryid' => 0,
                'courseid' => $attendance->course,
                'userid' => auth()->user()->id,
                'modulename' => 'attendance',
                'instance' => $id,
                'type'  => 0,
                'eventtype' => 'attendance',
                'timestart' => $date->unix(),
                'timeduration' => isset($request->time_end)? Carbon::parse($request->time_end)->unix() : 0,
                'visible' => 1,
                'timemodified' => time(),
                'sequence' => 1,
            ]);

            DB::connection('moodle_mysql')
            ->table('mdl_attendance_sessions')
            ->where('id', $id)
            ->update([
                'caleventid' => $event->id,
                'calendarevent' => 1
            ]);

            if ($request->repeat_until) {
                $repeatUntil = Carbon::parse($request->repeat_until. ' '. $request->time_start);

                $currentDate = $date->copy();

                $dayOfWeek = $currentDate->dayOfWeek; 
                if ($dayOfWeek !== $request->repeat_day) {
                    $daysMap = [
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday'
                    ];
                    $nextDay = $daysMap[(int)$request->repeat_day];
                    $currentDate->modify("next $nextDay");
                }

                while ($currentDate->lte($repeatUntil)) {
                    
                    // Tambahkan sesi baru untuk setiap hari Senin
                    $newId = DB::connection('moodle_mysql')
                        ->table('mdl_attendance_sessions')
                        ->insertGetId([
                            'attendanceid' => $attendance->id,
                            'sessdate' => $currentDate->setTimezone('Asia/Makassar')->unix(),
                            'duration' => $duration,
                            'timemodified' => time(),
                            'studentscanmark' => $request->studentscanmark,
                            'allowupdatestatus' => 0,
                            'description' => '',
                            'descriptionformat' => 1,
                            'autoassignstatus' => 0,
                            'studentpassword' => '',
                            'automark' => 0,
                            'automarkcompleted' => 0,
                            'automarkcmid' => 0,
                            'absenteereport' => 1,
                            'includeqrcode' => 0,
                            'rotateqrcode' => 0,
                            'rotateqrcodesecret' => '',
                        ]);
            
                    $newEvent = Event::create([
                        'name' => $attendance->name,
                        'description' => '',
                        'format' => 1,
                        'categoryid' => 0,
                        'courseid' => $attendance->course,
                        'userid' => auth()->user()->id,
                        'modulename' => 'attendance',
                        'instance' => $newId,
                        'type'  => 0,
                        'eventtype' => 'attendance',
                        'timestart' => $currentDate->unix(),
                        'timeduration' => isset($request->time_end) ? Carbon::parse($request->time_end)->unix() : 0,
                        'visible' => 1,
                        'timemodified' => time(),
                        'sequence' => 1,
                    ]);
            
                    DB::connection('moodle_mysql')
                        ->table('mdl_attendance_sessions')
                        ->where('id', $newId)
                        ->update([
                            'caleventid' => $newEvent->id,
                            'calendarevent' => 1
                        ]);
            
                    // Tambahkan 1 minggu ke tanggal saat ini
                    $currentDate->addWeek();
                    Log::info($currentDate);
                }
            }
            

            DB::connection('moodle_mysql')->commit();
            GlobalHelper::rebuildCourseCache($attendance->course);

            return response()->json([
                'message' => 'Session added successfully'
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateSession(Request $request, $sessionId){

        $date = Carbon::parse($request->date. ' '. $request->time_start);

        $duration = 0;

        if(isset($request->time_end)){
            $time_end = Carbon::parse($request->date. ' '. $request->time_end);
            $duration = $time_end->setTimezone('Asia/Makassar')->unix() - $date->setTimezone('Asia/Makassar')->unix();
        }

        DB::connection('moodle_mysql')
        ->table('mdl_attendance_sessions')
        ->where('id', $sessionId)
        ->update([
            'sessdate' => $date->setTimezone('Asia/Makassar')->unix(),
            'duration' => $duration,
            'timemodified' => time(),
            'studentscanmark' => $request->studentscanmark,
        ]);

        Event::where('eventtype', 'attendance')
        ->where('modulename', 'attendance')
        ->where('instance', $sessionId)
        ->update([
            'timestart' => $date->setTimezone('Asia/Makassar')->unix(),
            'timeduration' => isset($request->time_end)? Carbon::parse($request->time_end)->unix() : 0,
            'timemodified' => time(),
        ]);

        return response()->json([
            'message' => 'Session updated successfully'
        ]);

    }

    public function deleteSession(Request $request){

        DB::connection('moodle_mysql')->beginTransaction();

        try {

            foreach ($request->sessions as $session) {

                Event::where('eventtype', 'attendance')
                ->where('modulename', 'attendance')
                ->where('instance', $session)
                ->delete();

                DB::connection('moodle_mysql')
                ->table('mdl_attendance_sessions')
                ->where('id', $session)
                ->delete();
            }

            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'Session deleted successfully'
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function getSessionDetail($shortname, $sessionId){

        $course = Course::where('shortname', $shortname)->first();

        $module = Module::where('name', 'attendance')->first();

        $role = Role::where('shortname', 'student')->first();

        $session = DB::connection('moodle_mysql')->table('mdl_attendance_sessions')
        ->where('id', $sessionId)
        ->first(['id', 'attendanceid', 'sessdate']);

        $cm = CourseModule::where('instance', $session->attendanceid)
        ->where('module', $module->id)
        ->where('course', $course->id)
        ->first();

        $ctx = Context::where('contextlevel', 70)->where('instanceid', $cm->id)->first();

        $attendance_statusses = DB::connection('moodle_mysql')->table('mdl_attendance_statuses')
        ->where('attendanceid', $session->attendanceid)
        ->get();

        $attendance_log = DB::connection('moodle_mysql')->table('mdl_attendance_log')
        ->where('sessionid', $sessionId)
        ->get();

        $roleAssignmentsQuery = DB::connection('moodle_mysql')->table('mdl_role_assignments')
        ->select('userid')
        ->distinct()
        ->whereIn('contextid', explode('/', $ctx->path))
        ->whereIn('roleid', [$role->id]);

        $subQuery = DB::connection('moodle_mysql')->table('mdl_user as eu1_u')
        ->select('eu1_u.id')
        ->distinct()
        ->join('mdl_user_enrolments as ej1_ue', 'ej1_ue.userid', '=', 'eu1_u.id')
        ->join('mdl_enrol as ej1_e', function ($join) use ($course) {
            $join->on('ej1_e.id', '=', 'ej1_ue.enrolid')
                ->where('ej1_e.courseid', '=', $course->id);
        })
        ->whereExists(function ($query) use ($roleAssignmentsQuery) {
            $query->select(DB::raw(1))
                ->from(DB::raw('(' . $roleAssignmentsQuery->toSql() . ') as ra'))
                ->whereRaw('ra.userid = eu1_u.id')
                ->mergeBindings($roleAssignmentsQuery);
        })
        ->where('eu1_u.deleted', '=', 0)
        ->where('eu1_u.id', '<>', 1);

        $users = DB::connection('moodle_mysql')->table('mdl_user as u')
            ->select(
                'u.id',
                'u.email',
                'u.picture',
                'u.firstname',
                DB::raw("CONCAT(u.firstname, ' ', u.lastname) as fullname"),
                'u.lastname',
                'u.middlename',
                'u.alternatename',
                'u.imagealt',
                'u.username',
            )
        ->joinSub($subQuery, 'je', 'je.id', '=', 'u.id')
        ->where('u.deleted', '=', 0)
        ->orderBy('u.lastname')
        ->orderBy('u.firstname')
        ->orderBy('u.id')
        ->get();

        foreach ($users as $user) {
            $status = $attendance_log->where('studentid', $user->id)->first();
            $user->status = $status->statusid ?? 0;
            $user->note = $status->remarks ?? '';
        }

        $session->date = Carbon::parse($session->sessdate)->setTimezone('Asia/Makassar')->translatedFormat('l, d F Y');

        $data = [
            'attendance_statusses' => $attendance_statusses,
            'students' => $users,
            'session' => $session
        ];

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ], 200);

    }

    public function saveSessionDetail(Request $request, $attendance, $sessionId){

        $session = DB::connection('moodle_mysql')->table('mdl_attendance_sessions')
        ->where('id', $sessionId)
        ->first(['id', 'attendanceid']);

        $attendance_statusses = DB::connection('moodle_mysql')->table('mdl_attendance_statuses')
        ->where('attendanceid', $session->attendanceid)
        ->get();

        $statusset = implode(',',$attendance_statusses->pluck('id')->toArray());

        DB::connection('moodle_mysql')->beginTransaction();

        try {
            
            foreach ($request->students as $student) {
            
                DB::connection('moodle_mysql')->table('mdl_attendance_log')
                ->where('sessionid', $sessionId)
                ->updateOrInsert([
                    'sessionid' => $sessionId,
                    'studentid' => $student['id'],
                ], [
                    'statusid' => $student['status'],
                    'statusset' => $statusset,
                    'remarks' => $student['note'],
                    'timetaken' => time(),
                ]);

            }

            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'Session detail saved successfully'
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

}
