<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
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

    #[Rule('nullable', message: 'Tanggal harus diisi')]
    public $date;

    #[Rule('nullable', message: 'Waktu mulai harus diisi')]
    public $starttime;

    #[Rule('nullable', message: 'Waktu akhir harus diisi')]
    public $endtime;

    #[Rule('nullable')]
    public $is_repeat;

    #[Rule('nullable', message:'Pengisi kehadiran harus diisi')]
    public $filled_by;

    #[Rule('nullable')]
    public $repeat_attempt;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setInstance(Attendance $attendance){
        $this->attendance = $attendance;
        $this->fill([
            'name' => $attendance->name,
            'description' => $attendance->intro,
        ]);
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function store(){

        DB::beginTransaction();

        try {

            $instance = Attendance::create([
                'course' => $this->course->id,
                'name' => $this->name,
                'intro' => $this->description,
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
            ];

            $instance->statuses()->createMany($status);

            $cm = CourseHelper::addCourseModule($this->course->id, $this->module->id, $instance->id);
            CourseHelper::addContext($cm->id, $this->course->id);
            CourseHelper::addCourseModuleToSection($this->course->id, $cm->id, $this->section_num);
            DB::commit();
            GlobalHelper::rebuildCourseCache($this->course->id);
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
                'intro' => $this->description,
            ]);
            DB::commit();
            GlobalHelper::rebuildCourseCache($this->course->id);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

}
