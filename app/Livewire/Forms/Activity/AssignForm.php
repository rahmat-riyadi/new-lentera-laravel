<?php

namespace App\Livewire\Forms\Activity;

use App\Http\Controllers\CourseModuleController;
use App\Models\Course;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AssignForm extends Form
{

    public function __construct() {
        $this->module = Module::where('name', 'assign')->first();
        $this->submissiontype = 'onlinetext';
    }

    public Module $module;
    
    public ?Course $course;

    public $section_num;

    #[Rule('required', message: 'field nama harus diisi')]
    public $name;

    #[Rule('required', message: 'field deskripsi harus diisi')]
    public $intro;

    #[Rule('required')]
    public $duedatetype;

    public $duedate;

    public $duedate_end_date;

    public $duedate_end_time;
    
    #[Rule('required')]
    public $allowsubmissionsfromdate;

    public $duedate_start_date;
    
    public $duedate_start_time;

    #[Rule('required')]
    public $submissiontype;
    
    #[Rule('nullable')]
    public $wordlimit;

    #[Rule('nullable')]
    public $maxfilesubmissions;

    #[Rule('nullable')]
    public $max_file_size;

    #[Rule('nullable')]
    public $file_types;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function store(){

        if($this->duedatetype == 'time'){
            $currDay = Carbon::now()->format('Y-m-d');
            $this->allowsubmissionsfromdate = "{$currDay} {$this->duedate_start_time}";
            $this->allowsubmissionsfromdate = strtotime($this->allowsubmissionsfromdate);
        } else {
            $this->allowsubmissionsfromdate = "{$this->duedate_start_date} {$this->duedate_start_time}";
            $this->allowsubmissionsfromdate = strtotime($this->allowsubmissionsfromdate);
        }

        if($this->duedatetype == 'time'){
            $currDay = Carbon::now()->format('Y-m-d');
            $this->duedate = "{$currDay} {$this->duedate_end_time}";
            $this->duedate = strtotime($this->duedate);
        } else {
            $this->duedate = "{$this->duedate_end_date} {$this->duedate_end_time}";
            $this->duedate = strtotime($this->duedate);
        }



        // Log::debug($this->duedatetype);
        // Log::debug($this->duedate_end_date);
        // Log::debug($this->duedate_end_time);
        // Log::debug($this->duedate);
        // Log::debug($this->allowsubmissionsfromdate);
        // Log::debug($this->all());
        // return;

        $instance = $this->course->assignment()->create([
            'name' => $this->name,
            'intro' => $this->intro,
            'introformat' => 1,
            'alwaysshowdescription' => 1,
            'markingallocation' => 0,
            'submissiondrafts' => 0,
            'requiresubmissionstatement' => 0,
            'sendlatenotifications' => 1,
            'sendstudentnotifications' => 1,
            'cutoffdate' => 0,
            'gradingduedate' => 0,
            'grade' => 100,
            'completionsubmit' => 100,
            'requireallteammemberssubmit' => 100,
            'blindmarking' => 100,
            'markingworkflow' => 100,
            'duedate' => $this->allowsubmissionsfromdate,
            'duedate' => $this->duedate,
            'timemodified' => time()
        ]);

        if($this->submissiontype == 'onlinetext'){
            $instance->assignPluginConfig()->create([
                'plugin' => 'onlinetext',
                'subtype' => 'assignsubmission',
                'name' => 'enabled',
                'value' => 1,
            ]);

            $instance->assignPluginConfig()->create([
                'plugin' => 'onlinetext',
                'subtype' => 'assignsubmission',
                'name' => 'wordlimitenabled',
                'value' => !is_null($this->wordlimit),
            ]);

            $instance->assignPluginConfig()->create([
                'plugin' => 'onlinetext',
                'subtype' => 'assignsubmission',
                'name' => 'wordlimit',
                'value' => $this->wordlimit ?? 0 ,
            ]);

        } else {

            $instance->assignPluginConfig()->create([
                'plugin' => 'file',
                'subtype' => 'assignsubmission',
                'name' => 'enabled',
                'value' => 1,
            ]);

            $instance->assignPluginConfig()->create([
                'plugin' => 'file',
                'subtype' => 'assignsubmission',
                'name' => 'maxfilesubmission',
                'value' => $this->maxfilesubmissions ?? 1,
            ]);

            $instance->assignPluginConfig()->create([
                'plugin' => 'file',
                'subtype' => 'assignsubmission',
                'name' => 'maxsubmissionsizebytes',
                'value' => $this->max_file_size ?? 1048576,
            ]);

            $instance->assignPluginConfig()->create([
                'plugin' => 'file',
                'subtype' => 'assignsubmission',
                'name' => 'filetypeslist',
                'value' => $this->file_types ?? '*',
            ]);

        }


        $cm = CourseModuleController::addCourseModule([
            'course' => $this->course->id,
            'module' => $this->module->id,
            'instance' => $instance->id,
            'showdescription' => '1',
            'added' => time()
        ]);

        CourseModuleController::addCourseModuleToSection(
            $this->course->id,
            $cm->id,
            $this->section_num
        );

    }

}
