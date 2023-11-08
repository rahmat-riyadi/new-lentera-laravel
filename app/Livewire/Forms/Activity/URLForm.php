<?php

namespace App\Livewire\Forms\Activity;

use App\Http\Controllers\CourseModuleController;
use App\Models\Course;
use App\Models\Module;
use Livewire\Attributes\Rule;
use Livewire\Form;

class URLForm extends Form
{

    public function __construct() {
        $this->module = Module::where('name', 'url')->first();
    }

    public Module $module;

    public ?Course $course;

    public $section_num;

    #[Rule('required')]
    public $name;
    
    #[Rule('required')]
    public $intro;

    #[Rule('required')]
    public $externalurl;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function store(){

        $instance = $this->course->url()->create([
            'name' => $this->name,
            'intro' => $this->intro,
            'externalurl' => $this->externalurl,
            'timemodified' => time()
        ]);

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
