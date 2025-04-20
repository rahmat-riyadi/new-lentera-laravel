<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Models\Context;
use App\Models\Course;
use App\Models\Module;
use App\Models\Resource;
use App\Models\ResourceFile;
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

    public $cm;

    #[Rule('required', message: 'Judul harus diisi')]
    public $name;

    #[Rule('required', message: 'Deskripsi harus diisi')]
    public $description;

    public $fileResource;

    public $newFileResource;

    public $uploadedFile = [];

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function setCm($cm){
        $this->cm = $cm;
    }

    public function setInstance(Resource $resource){
        $this->fill([
            'resource' => $resource,
            'name' => $resource->name,
            'description' => $resource->intro,
        ]);
        // $this->fileResource = $resource->files;
        $file_ctx = DB::connection('moodle_mysql')->table('mdl_context')
        ->where('instanceid', $this->cm)
        ->where('contextlevel', 70)
        ->first('id');

        $files = DB::connection('moodle_mysql')->table('mdl_files')
        ->where('contextid', $file_ctx->id)
        ->where('component', 'mod_resource')
        ->whereNotNull('mimetype')
        ->whereNotNull('source')
        ->orderBy('id')
        ->get();

        $this->fileResource = $files->map(function($e){

            $filedir = substr($e->contenthash, 0, 4);
            $formatted_dir = substr_replace($filedir, '/', 2, 0);

            $ext = explode('.',$e->filename);
            $ext = $ext[count($ext)-1];

            $e->name = $e->filename;
            $e->file = "/preview/file/$e->id/$e->filename";
            $e->size = $e->filesize;
            $e->itemid = $e->itemid;
            return $e;
        });   
    }

    public function insertFile($ctxid, $itemid){

        foreach($this->uploadedFile as $files){

            ResourceFile::create([
                'contenthash' => $files[0]->contenthash,
                'pathnamehash' => GlobalHelper::get_pathname_hash($files[0]->contextid, 'mod_resource', 'content', $files[0]->itemid, '/', $files[0]->filename),
                'contextid' => $ctxid,
                'component' => 'mod_resource',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $files[0]->filename,
                'userid' => $files[0]->userid,
                'filesize' => $files[0]->filesize,
                'mimetype' => $files[0]->mimetype,
                'status' => 0,
                'source' => $files[0]->filename,
                'author' => $files[0]->author,
                'license' => $files[0]->license,
                'timecreated' => $files[0]->timecreated,
                'timemodified' => $files[0]->timemodified,
                'sortorder' => 1,
                'referencefileid' => null,
            ]);
            
            ResourceFile::create([
                'component' => 'mod_resource',
                'filearea' => 'content',
                'contextid' => $ctxid,
                'contenthash' => $files[1]->contenthash,
                'pathnamehash' => GlobalHelper::get_pathname_hash($ctxid, 'mod_resource', 'content', $files[1]->itemid, '/', $files[1]->filename),
                'itemid' => 0,
                'filepath' => '/',
                'userid' => $files[1]->userid,
                'filename' => '.',
                'filesize' => 0,
                'timecreated' => $files[1]->timecreated,
                'timemodified' => $files[1]->timemodified,
                'sortorder' => 0,
            ]);
            

        }

    }

    public function store(){

        DB::beginTransaction();
        
        try {

            $instance = Resource::create([
                'course' => $this->course->id,
                'name' => $this->name,
                'intro' => $this->description,
            ]);
            
            $cm = CourseHelper::addCourseModule($this->course->id, $this->module->id, $instance->id);

            $context = Context::where('instanceid', $this->course->id)
            ->where('contextlevel', 50)
            ->first();

            $newContext = Context::create([
                'contextlevel' => 70,
                'instanceid' => $cm->id
            ]);

            $newContext->update([
                'path' => $context->path . '/' . $newContext->id,
                'depth' => substr_count($context->path, '/') + 1
            ]);
            // CourseHelper::addContext($cm->id, $this->course->id);
            CourseHelper::addCourseModuleToSection($this->course->id, $cm->id, $this->section_num);

            $this->insertFile($newContext->id, $instance->id);
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

            $this->resource->update([
                'name' => $this->name,
                'intro' => $this->description,
            ]);

            $file_ctx = DB::connection('moodle_mysql')->table('mdl_context')
            ->where('instanceid', $this->cm)
            ->where('contextlevel', 70)
            ->first('id');

            $this->insertFile($file_ctx->id, $this->resource->id);
         
            DB::commit();
            
            GlobalHelper::rebuildCourseCache($this->course->id);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

}
