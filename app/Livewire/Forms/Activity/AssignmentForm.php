<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Models\Assignment;
use App\Models\AssignmentConfig;
use App\Models\Course;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AssignmentForm extends Form
{
    function boot(){
        $this->module = Module::where('name', 'assign')->first();
    }
    
    public Module $module;
    
    public ?Assignment $assignment;

    public ?Course $course;

    public $section_num;


    #[Rule('required', message: 'Judul Tugas harus diisi')]
    public $name;

    #[Rule('nullable', message: 'Deskripsi harus diisi')]
    public $description;

    #[Rule('required', message: 'Jenis waktu pengiriman harus diiisi')]
    public $due_date_type;

    #[Rule('nullable', message: 'Waktu mulai harus diiisi')]
    public $start_date;

    #[Rule('nullable', message: 'Waktu mulai harus diiisi')]
    public $start_time;

    #[Rule('required', message: 'Waktu berakhir harus diiisi')]
    public $due_date;

    #[Rule('nullable', message: 'Waktu berakhir harus diiisi')]
    public $due_time;

    #[Rule('nullable', message: 'Nilai harus diiisi')]
    public $grade;

    #[Rule('nullable', message: 'Nilai harus diiisi')]
    public $activity_remember;


    // ====================== Assign Plugin Config ====================== //

    #[Rule('required', message: 'Jenis pengiriman diiisi')]
    public $submission_type;

    public $word_limit;

    public $max_size;

    public $file_types;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function setInstance(Assignment $assignment){
        $this->assignment = $assignment;
        $this->fill([
            'name' => $assignment->name,
            'description' => $assignment->description,
            'activity_remember' => $assignment->activity_remember,
        ]);

        $start_date = Carbon::parse($assignment->start_date);
        $end_date = Carbon::parse($assignment->due_date);

        $this->start_date = $start_date->format('Y-m-d');
        $this->due_date = $end_date->format('Y-m-d');

        $this->start_time = $start_date->format('H:i');
        $this->due_time = $end_date->format('H:i');

        if($start_date->diffInDays($end_date) == 0){
            $this->due_date_type = 'time';
        } else {
            $this->due_date_type = 'date';
        }

        $type = $assignment->configs()->where('name', 'type')->first();

        $this->submission_type = $type->value;

        if($type->value == 'onlinetext'){
            $wordlimit = $assignment->configs()->where('name', 'wordlimit')->first();
            $this->word_limit = $wordlimit->value;
        }

        if($type->value == 'file'){
            $wordlimit = $assignment->configs()->where('name', 'maxsize')->first();
            $this->max_size = $wordlimit->value;

            $wordlimit = $assignment->configs()->where('name', 'filetypes')->first();
            $this->file_types = $wordlimit->value;
        }

    }

    public function store(){

        $start_date = Carbon::parse($this->start_date . ' '. $this->start_time );
        $due_date = Carbon::parse($this->due_date . ' '. $this->due_time );

        DB::beginTransaction();

        try {

            $instance = $this->course->assignment()->create([
                'name' => $this->name,
                'description' => $this->description,
                'due_date' => $due_date,
                'start_date' => $start_date,
                'grade' => 100,
                'activity_remember' => $this->activity_remember,
            ]);

            if($this->submission_type == 'onlinetext'){
                $instance->configs()->createMany([
                    [
                    'name' => 'type',
                    'value' => 'onlinetext',
                    ],
                    [
                    'name' => 'wordlimit',
                    'value' => $this->word_limit ?? 0,
                    ]
                ]);
            }

            if($this->submission_type == 'file'){
                $instance->configs()->createMany([
                    [
                    'name' => 'type',
                    'value' => 'file',
                    ],
                    [
                        'name' => 'maxsize',
                        'value' => $this->max_size ?? 0,
                    ],
                    [
                        'name' => 'filetypes',
                        'value' => $this->max_size ?? "*",
                    ]
                ]);
            }

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

        $start_date = Carbon::parse($this->start_date . ' '. $this->start_time );
        $due_date = Carbon::parse($this->due_date . ' '. $this->due_time );

        DB::beginTransaction();

        try {
            $this->assignment->update([
                'name' => $this->name,
                'description' => $this->description,
                'start_date' => $start_date,
                'due_date' => $due_date,
                'activity_remember' => $this->activity_remember,
            ]);

            $this->assignment->configs()->delete();

            if($this->submission_type == 'onlinetext'){
                $this->assignment->configs()->createMany([
                    [
                    'name' => 'type',
                    'value' => 'onlinetext',
                    ],
                    [
                    'name' => 'wordlimit',
                    'value' => $this->word_limit ?? 0,
                    ]
                ]);
            }

            if($this->submission_type == 'file'){
                $this->assignment->configs()->createMany([
                    [
                    'name' => 'type',
                    'value' => 'file',
                    ],
                    [
                        'name' => 'maxsize',
                        'value' => $this->max_size ?? 0,
                    ],
                    [
                        'name' => 'filetypes',
                        'value' => $this->max_size ?? "*",
                    ]
                ]);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

}
