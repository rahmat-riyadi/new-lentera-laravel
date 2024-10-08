<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Models\Assignment;
use App\Models\AssignmentConfig;
use App\Models\Context;
use App\Models\Course;
use App\Models\GradeCategory;
use App\Models\GradeGrades;
use App\Models\GradeItem;
use App\Models\GradingArea;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Rule;
use Livewire\Form;
use stdClass;

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

    // ====================== Assign Files ====================== //

    public $file;

    public $files;

    public $oldFiles;

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
            'description' => $assignment->intro,
            'activity_remember' => $assignment->allowsubmissionsfromdate,
        ]);

        $start_date = Carbon::parse($assignment->allowsubmissionsfromdate);
        $end_date = Carbon::parse($assignment->duedate);

        $this->start_date = $start_date->format('Y-m-d');
        $this->due_date = $end_date->format('Y-m-d');

        $this->start_time = $start_date->format('H:i');
        $this->due_time = $end_date->format('H:i');

        if($start_date->diffInDays($end_date) == 0){
            $this->due_date_type = 'time';
        } else {
            $this->due_date_type = 'date';
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
            
        Log::info(($online_text_plugin));
        Log::info(($file_plugin));

        Log::info((boolean)$online_text_plugin['enabled']);

        if((boolean)$online_text_plugin['enabled']){
            $this->submission_type = 'onlinetext';    
            $this->word_limit = $online_text_plugin['wordlimit'];
        }

        if((boolean)$file_plugin['enabled']){
            $this->submission_type = 'file';    
            $this->max_size = $file_plugin['maxsubmissionsizebytes'];    
        }

    }

    public function store(){

        $start_date = Carbon::parse($this->start_date . ' '. $this->start_time );
        $due_date = Carbon::parse($this->due_date . ' '. $this->due_time );

        DB::beginTransaction();

        try {

            $instance = $this->course->assignment()->create([
                'name' => $this->name,
                'intro' => $this->description,
                'allowsubmissionsfromdate' => $start_date->unix(),
                'duedate' => $due_date->unix(),
                'grade' => 100,
            ]);

            if($this->submission_type == 'onlinetext'){
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
                        'value' => $this->word_limit,
                    ],
                ]);
            }

            if($this->submission_type == 'file'){
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
                        'value' => 1,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'maxsubmissionsizebytes',
                        'value' => $this->max_size,
                    ],
                    [
                        'plugin' => 'file',
                        'subtype' => 'assignsubmission',
                        'name' => 'filetypeslist',
                        'value' => '',
                    ],
                ]);
            }

            $GradeCategory = GradeCategory::firstWhere("courseid", $this->course->id);

            $gradeItem = GradeItem::create([
                'courseid' => $this->course->id,
                'categoryid' => $GradeCategory->id,
                'name' => $this->assignment->name,
                'itemtype' => 'mod',
                'itemmodule' => 'assign',
                'iteminstance' => $this->assignment->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $context = Context::where('instanceid', $this->course->id)
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

            GradeGrades::insert($grade_grades_data);

            $cm = CourseHelper::addCourseModule($this->course->id, $this->module->id, $instance->id);
            $ctx = CourseHelper::addContext($cm->id, $this->course->id);
            CourseHelper::addCourseModuleToSection($this->course->id, $cm->id, $this->section_num);

            GradingArea::create([
                'contextid' => $ctx->id,
                'component' => 'mod_assign',
                'areaname' => 'submissions',
            ]);

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
                'intro' => $this->description,
                'allowsubmissionsfromdate' => $start_date->unix(),
                'duedate' => $due_date->unix(),
            ]);

            if($this->submission_type == 'onlinetext'){

                AssignmentConfig::where('plugin', 'onlinetext')
                ->where('assignment', $this->assignment->id)
                ->where('subtype', 'assignsubmission')
                ->where('name', 'enabled')
                ->update([
                    'value' => 1
                ]);

                AssignmentConfig::where('plugin', 'file')
                ->where('assignment', $this->assignment->id)
                ->where('subtype', 'assignsubmission')
                ->where('name', 'enabled')
                ->update([
                    'value' => 0
                ]);

                if($this->word_limit){
                    AssignmentConfig::where('plugin', 'onlinetext')
                    ->where('assignment', $this->assignment->id)
                    ->where('subtype', 'assignsubmission')
                    ->where('name', 'wordlimit')
                    ->update([
                        'value' => $this->word_limit
                    ]);
                }

            }

            if($this->submission_type == 'file'){

                AssignmentConfig::where('plugin', 'file')
                ->where('assignment', $this->assignment->id)
                ->where('subtype', 'assignsubmission')
                ->where('name', 'enabled')
                ->update([
                    'value' => 1
                ]);

                AssignmentConfig::where('plugin', 'onlinetext')
                ->where('assignment', $this->assignment->id)
                ->where('subtype', 'assignsubmission')
                ->where('name', 'enabled')
                ->update([
                    'value' => 0
                ]);
                
                if($this->max_size){
                    AssignmentConfig::where('plugin', 'file')
                    ->where('assignment', $this->assignment->id)
                    ->where('subtype', 'assignsubmission')
                    ->where('name', 'maxsubmissionsizebytes')
                    ->update([
                        'value' => $this->max_size
                    ]);
                }

            }
            DB::commit();
            GlobalHelper::rebuildCourseCache($this->course->id);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

}
