<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AttendanceForm extends Form
{
    function boot(){
        $this->module = Module::where('name', 'attendance')->first();
    }

    public Module $module;

    public ?Attendance $attendance;

    public ?Course $course;

    public $section_num;

    #[Rule('required', message: 'Judul harus diisi')]
    public $name;
    
    #[Rule('required', message: 'Deskripsi harus diisi')]
    public $description;

    #[Rule('required', message: 'Tanggal harus diisi')]
    public $date;

    #[Rule('required', message: 'Waktu mulai harus diisi')]
    public $starttime;

    #[Rule('required', message: 'Waktu akhir harus diisi')]
    public $endtime;

    #[Rule('nullable')]
    public $is_repeat;

    #[Rule('required', message:'Pengisi kehadiran harus diisi')]
    public $filled_by;

    #[Rule('nullable')]
    public $repeat_attempt;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setInstance(Attendance $attendance){
        $this->attendance = $attendance;
        $this->fill($attendance);
        $this->is_repeat = $this->is_repeat == 1 ? 'on' : '';
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function store(){

        DB::beginTransaction();

        try {

            $instance = Attendance::create([
                'course_id' => $this->course->id,
                'name' => $this->name,
                'description' => $this->description,
                'starttime' => $this->starttime,
                'endtime' => $this->endtime,
                'date' => $this->date,
                'is_repeat' => isset($this->is_repeat),
                'repeat_attempt' => $this->repeat_attempt ?? 0,
                'filled_by' => $this->filled_by,
            ]);

            $role = Role::where('shortname', 'student')->first();

            $participantsData = DB::connection('moodle_mysql')
            ->table('mdl_enrol')
            ->where('mdl_enrol.courseid', '=', $this->course->id)
            ->where('mdl_enrol.roleid', '=', $role->id)
            ->where('mdl_user_enrolments.userid', '!=', auth()->user()->id)
            ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
            ->join('mdl_user', 'mdl_user.id', 'mdl_user_enrolments.userid')
            ->select('mdl_user.id')->get();

            $participantsData = $participantsData->map(function($val){
                return [
                    'student_id' => $val->id,
                ];
            });

            $instance->students()->createMany($participantsData);

            $cm = CourseHelper::addCourseModule($this->course->id, $this->module->id, $instance->id);
            CourseHelper::addContext($cm->id, $this->course->id);
            CourseHelper::addCourseModuleToSection($this->course->id, $cm->id, $this->section_num);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function update(){
        DB::beginTransaction();
        try {
            $this->attendance->update([
                'name' => $this->name,
                'description' => $this->description,
                'starttime' => $this->starttime,
                'endtime' => $this->endtime,
                'date' => $this->date,
                'is_repeat' => isset($this->is_repeat),
                'repeat_attempt' => $this->repeat_attempt ?? 0,
                'filled_by' => $this->filled_by,
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

}
