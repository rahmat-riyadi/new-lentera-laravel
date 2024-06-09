<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Models\Course;
use App\Models\Module;
use App\Models\Url;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Rule;
use Livewire\Form;

class UrlForm extends Form
{
    // public function __construct() {
    // }

    function boot(){
        $this->module = Module::where('name', 'url')->first();
    }

    public Module $module;

    public ?Url $urlInstance;

    public ?Course $course;

    public $section_num;

    #[Rule('required', message: 'Judul harus diisi')]
    public $name;
    
    #[Rule('required', message: 'Deskripsi harus diisi')]
    public $description;

    #[Rule('required', message: 'URL harus diisi')]
    public $url;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function setUrl(Url $url){
        $this->fill([
            'urlInstance' => $url,
            'name' => $url->name,
            'description' => $url->intro,
            'url' => $url->externalurl,
        ]);
    }

    public function store(){

        DB::beginTransaction();

        try {
            $instance = $this->course->url()->create([
                'name' => $this->name,
                'intro' => $this->description,
                'externalurl' => $this->url,
            ]);
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

        try {
            $this->urlInstance->update([
                'name' => $this->name,
                'intro' => $this->description,
                'externalurl' => $this->url,
            ]);
            GlobalHelper::rebuildCourseCache($this->course->id);
        } catch (\Throwable $th) {
            throw $th;
        }

    }

}
