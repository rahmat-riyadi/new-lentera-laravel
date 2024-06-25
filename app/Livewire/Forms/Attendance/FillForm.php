<?php

namespace App\Livewire\Forms\Attendance;

use App\Helpers\GlobalHelper;
use App\Models\Attendance;
use App\Models\Context;
use App\Models\CourseModule;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FillForm extends Form
{
    public Attendance $attendance;

    public $session_id;

    public $statuses;

    public $students = [];

    public function setModel(Attendance $attendance, CourseModule $courseModule, $session){
        $this->attendance = $attendance;

        $this->session_id = $session;

        $role = DB::connection('moodle_mysql')->
        table('mdl_role')->where('archetype', 'student')
        ->first();

        $courseContext = Context::where('contextlevel', 50)
        ->where('instanceid', $attendance->course)
        ->first();

        $context = Context::where('instanceid', $courseModule->id)
        ->where('contextlevel', 70)
        ->where('path', 'LIKE', "%$courseContext->id%")
        ->first();

        $contextPath = explode('/', $context->path);
        array_shift($contextPath);
        $contextPath = implode(',', $contextPath);

        $innerSubQuery = DB::connection('moodle_mysql')->table('mdl_user as eu1_u')
        ->join('mdl_user_enrolments as ej1_ue', 'ej1_ue.userid', '=', 'eu1_u.id')
        ->join('mdl_enrol as ej1_e', function($join) use ($attendance) {
            $join->on('ej1_e.id', '=', 'ej1_ue.enrolid')
                ->where('ej1_e.courseid', '=', $attendance->course);
        })
        ->join(DB::connection('moodle_mysql')
        ->raw("(SELECT DISTINCT userid FROM mdl_role_assignments WHERE contextid IN ($contextPath) AND roleid IN ($role->id)) as ra"), 'ra.userid', '=', 'eu1_u.id')
        ->where('eu1_u.deleted', '=', 0)
        ->where('eu1_u.id', '<>', 1)
        ->select('eu1_u.id')
        ->distinct();

    
        $mainQuery = DB::connection('moodle_mysql')->table('mdl_user as u')
        ->joinSub($innerSubQuery, 'je', function($join) {
            $join->on('je.id', '=', 'u.id');
        })
        ->where('u.deleted', '=', 0)
        ->select(
            'u.id', 
            'u.email', 
            'u.picture', 
            'u.firstname',
            'u.lastname', 
            'u.middlename', 
            'u.imagealt', 
            'u.username'
        )
        ->orderBy('u.lastname')
        ->orderBy('u.firstname')
        ->orderBy('u.id')
        ->get();

        $log = DB::connection('moodle_mysql')
        ->table('mdl_attendance_log')
        ->where('sessionid', $session)
        ->get();

        $this->statuses = DB::connection('moodle_mysql')
        ->table('mdl_attendance_statuses')
        ->where('attendanceid', $attendance->id)
        ->get();

        $this->students = $mainQuery->map(function ($student) use ($log) {


            $img = DB::connection('moodle_mysql')->table('mdl_files')
            ->where('id', $student->picture)
            ->first();

            $record = [
                'id' => $student->id,
                'name' => $student->firstname.' '. $student->lastname,
                'nim' => $student->username,
                'picture' => $student->picture == 0 ? null : url("/preview/file/$img->id/$img->filename"),
            ];

            $studentLog = $log->firstWhere('studentid', $student->id);

            $record['status'] = null;

            if($studentLog){
                $status = $this->statuses->firstWhere('id', $studentLog->statusid);
                switch ($status->acronym) {
                    case 'P':
                        $record['status'] = 'Hadir';
                        break;
                    case 'A':
                        $record['status'] = 'Alpa';
                        break;
                    case 'L':
                        $record['status'] = 'Terlambat';
                        break;
                    case 'E':
                        $record['status'] = 'Izin';
                        break;
                    case 'S':
                        $record['status'] = 'Sakit';
                        break;
                }
            }

            return $record;
        });

    }

    public function submit(){
        try {
            
            foreach($this->students as $s){

                if(!is_null($s['status'])){
                    switch ($s['status']) {
                        case 'Hadir':
                            $status = $this->statuses->firstWhere('description', 'Present'); 
                            break;
                        case 'Izin':
                            $status = $this->statuses->firstWhere('description', 'Excused'); 
                            break;
                        case 'Sakit':
                            $status = $this->statuses->firstWhere('description', 'Sick'); 
                            break;
                        case 'Alpa':
                            $status = $this->statuses->firstWhere('description', 'Absent'); 
                            break;
                        case 'Terlambat':
                            $status = $this->statuses->firstWhere('description', 'Late'); 
                            break;
                    }

                    DB::connection('moodle_mysql')
                    ->table('mdl_attendance_log')
                    ->where('sessionid', $this->session_id)
                    ->updateOrInsert(
                        [
                            'studentid' => $s['id'],
                        ],
                        [
                            'statusid' => $status->id,
                            'statusset' => $this->statuses->pluck('id')->join(','),
                            'timetaken' => time(),
                            'takenby' => auth()->user()->id,
                            'sessionid' => $this->session_id
                        ]
                    );
                }
                
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
