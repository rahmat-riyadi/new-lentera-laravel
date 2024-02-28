<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Models\Course;
use App\Models\Module;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ResourceForm extends Form
{
    function boot(){
        $this->module = Module::where('name', 'resource')->first();
    }

    public Module $module;

    public ?Resource $resource;

    public ?Course $course;

    public $section_num;

    #[Rule('required', message: 'Judul harus diisi')]
    public $name;

    #[Rule('required', message: 'Deskripsi harus diisi')]
    public $description;

    public $fileResource;

    public $newFileResource;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function setInstance(Resource $resource){
        $this->fill([
            'resource' => $resource,
            'name' => $resource->name,
            'description' => $resource->description,
        ]);
        $this->fileResource = $resource->files;
    }

    public function store(){

        DB::beginTransaction();
        
        try {

            $instance = Resource::create([
                'course_id' => $this->course->id,
                'name' => $this->name,
                'description' => $this->description,
            ]);
            
            foreach($this->fileResource as $file){
                $name = $file->store('resource');
                $instance->files()->create([
                    'file' => $name,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
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

        DB::beginTransaction();
        
        try {

            $this->resource->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);
            
            foreach($this->newFileResource ?? [] as $file){
                $name = $file->store('resource');
                $this->resource->files()->create([
                    'file' => $name,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
                ]);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

}
