<?php

namespace App\Livewire\Forms\Attendance;

use App\Helpers\GlobalHelper;
use App\Models\Attendance;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SessionForm extends Form
{
    public ?Attendance $attendance;

    public $session_id;

    #[Validate('required', message: 'Tanggal harus diisi')]
    public $date;

    #[Validate('required', message: 'Waktu mulai harus diisi')]
    public $time_start;

    #[Validate(
        'nullable|after:time_start', 
        message: [
            'after' => 'Waktu akhir harus lebih besar dari waktu mulai'
        ]
    )]
    public $time_end;

    #[Validate('nullable')]
    public $fillable_type;

    public function setInstance($session){

        $this->session_id = $session;

        $session = DB::connection('moodle_mysql')->table('mdl_attendance_sessions')
        ->where('id', $session)
        ->first();

        $date = Carbon::parse($session->sessdate);

        $this->date = $date->setTimezone('Asia/Makassar')->format('Y-m-d');
        $this->time_start = $date->setTimezone('Asia/Makassar')->format('H:i');

        if($session->duration > 0) {
            $this->time_end = Carbon::parse($session->sessdate + $session->duration)->setTimezone('Asia/Makassar')->format('H:i');
        }

        if($session->studentscanmark){
            $this->fillable_type = 'mahasiswa';
        } else {
            $this->fillable_type = 'dosen';
        }

    }

    public function store(){

        DB::beginTransaction();
        
        try {
            //code...
            
            $date = Carbon::parse($this->date. ' '. $this->time_start);


            $duration = 0;

            if(isset($this->time_end)){
                $time_end = Carbon::parse($this->date. ' '. $this->time_end);
                $duration = $time_end->setTimezone('Asia/Makassar')->unix() - $date->setTimezone('Asia/Makassar')->unix();
            }

            $id = DB::connection('moodle_mysql')
            ->table('mdl_attendance_sessions')
            ->insertGetId([
                'attendanceid' => $this->attendance->id,
                'sessdate' => $date->setTimezone('Asia/Makassar')->unix(),
                'duration' => $duration,
                'timemodified' => time(),
                'studentscanmark' => $this->fillable_type == 'mahasiswa' ? 1 : 0,
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
                'name' => $this->attendance->name,
                'description' => '',
                'format' => 1,
                'categoryid' => 0,
                'courseid' => $this->attendance->course,
                'userid' => auth()->user()->id,
                'modulename' => 'attendance',
                'instance' => $id,
                'type'  => 0,
                'eventtype' => 'attendance',
                'timestart' => $date->unix(),
                'timeduration' => isset($this->time_end)? Carbon::parse($this->time_end)->unix() : 0,
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

            DB::commit();
            GlobalHelper::rebuildCourseCache($this->attendance->course);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        

    }

    public function update(){

        DB::beginTransaction();

        try {

            $date = Carbon::parse($this->date. ' '. $this->time_start);

            $duration = 0;

            if(isset($this->time_end)){
                $time_end = Carbon::parse($this->date. ' '. $this->time_end);
                $duration = $time_end->unix() - $date->unix();
            }
            
            DB::connection('moodle_mysql')->table('mdl_attendance_sessions')
            ->where('id', $this->session_id)
            ->update([
                'sessdate' => $date->unix(),
                'duration' => $duration,
                'timemodified' => time(),
                'studentscanmark' => $this->fillable_type == 'mahasiswa' ? 1 : 0,
            ]);

            DB::commit();
            GlobalHelper::rebuildCourseCache($this->attendance->course);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }        

    }

}
