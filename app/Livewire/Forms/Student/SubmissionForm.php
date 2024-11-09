<?php

namespace App\Livewire\Forms\Student;

use App\Helpers\GlobalHelper;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Context;
use App\Models\CourseModule;
use App\Models\Module;
use App\Models\ResourceFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Form;

class SubmissionForm extends Form
{
    public Assignment $assignment;
    public ?AssignmentSubmission $assignmentSubmission;

    public $file;

    public $files = [];

    public $oldFiles;

    public $url;

    public $submission_type;

    public $submission_file_number;

    public function setInstance(Assignment $assignment, CourseModule $courseModule){

        $this->assignment = $assignment;

        $online_text_plugin = $assignment->configs()
        ->where('subtype', 'assignsubmission')
        ->where('plugin', 'onlinetext')
        ->where('name', 'enabled')
        ->where('value', 1)
        ->first();

        $file_plugin = $assignment->configs()
        ->where('subtype', 'assignsubmission')
        ->where('plugin', 'file')
        ->where('name', 'enabled')
        ->where('value', 1)
        ->first();

        if($online_text_plugin){
            $this->submission_type = 'onlinetext';
        } 

        if($file_plugin){
            $this->submission_type = 'file';
            $this->submission_file_number = $assignment->configs()
            ->where('subtype', 'assignsubmission')
            ->where('plugin', 'file')
            ->where('name', 'maxfilesubmissions')
            ->first('value')->value;
        }

        $submission = AssignmentSubmission::where('userid', auth()->user()->id)
        ->where('assignment', $assignment->id)
        ->orderBy('id')
        ->first();

        if($submission){

            if($this->submission_type == 'file'){
                $this->assignmentSubmission = $submission;
                $ctx_id = Context::where('contextlevel', 70)->where('instanceid', $courseModule->id)->first('id')->id;
        
                $files = DB::connection('moodle_mysql')->table('mdl_files')
                ->where('contextid', $ctx_id)
                ->where('component', 'assignsubmission_file')
                ->where('filearea', 'submission_files')
                ->where('itemid', $submission->id)
                ->where('filename', '!=', '.')
                ->orderBy('id')
                ->get();

                $this->oldFiles = $files->map(function($e){
                    $e->id = $e->id;
                    $e->name = $e->filename;
                    $e->file = "/preview/file/$e->id/$e->filename";
                    $e->size = number_format($e->filesize / 1024 / 1024, 2) . ' MB';
                    $e->itemid = $e->itemid;
                    return $e;
                })->toArray();   

                Log::info($this->oldFiles);

            }




        }


    }

    public function setModel(Assignment $assignment){
        $this->assignment = $assignment;
    }
    public function setSubmission(AssignmentSubmission $assignmentSubmission){
        $this->assignmentSubmission = $assignmentSubmission;
    }

    public function submitFiles(){
        DB::beginTransaction();

        try {

            if(empty($this->assignmentSubmission)){
                $instance = AssignmentSubmission::create([
                    'assignment_id' => $this->assignment->id,
                    'student_id' => auth()->user()->id,
                ]);
            } else {
                $instance = $this->assignmentSubmission;
            }


            foreach ($this->files ?? [] as $file) {
                $path = $file->store('assignment-submission');
                $instance->files()->create([
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }   
    }

    public function submitUrl(){
        DB::beginTransaction();
        try {

            if(empty($this->assignmentSubmission)){
                $instance = AssignmentSubmission::create([
                    'assignment_id' => $this->assignment->id,
                    'student_id' => auth()->user()->id,
                ]);
            } else {
                $instance = $this->assignmentSubmission;
            }

            $instance->url()->updateOrCreate(
                [
                    'assignment_submission_id' => $instance->id
                ],
                [
                    'url' => $this->url
                ]
            );

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

    public function submitAssignment(){

        DB::beginTransaction();

        try {

            DB::connection('moodle_mysql')->table('mdl_assign_submission')
            ->updateOrInsert(
                [
                    'assignment' => $this->assignment->id,
                    'userid' => auth()->user()->id,
                ],[
                    'assignment' => $this->assignment->id, 
                    'userid' => auth()->user()->id,
                    'status' => 'new',
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'groupid' => 0,
                    'attemptnumber' => 0,
                    'latest' => 1,
                    'timestarted' => null,
                ]
            );

            $submission = AssignmentSubmission::where('userid', auth()->user()->id)
            ->where('assignment', $this->assignment->id)->first();

            DB::connection('moodle_mysql')->table('mdl_assignsubmission_file')
            ->updateOrInsert(
                [
                    'assignment' => $this->assignment->id,
                    'submission' => $submission->id
                ],[
                    'numfiles' => count($this->files),
                ]
            );

            $mod_id = Module::where('name', 'assign')->first('id');

            $cm = CourseModule::where("module", $mod_id->id)
            ->where('instance', $this->assignment->id)
            ->first();

            $context = Context::where('instanceid', $cm->id)
            ->where('contextlevel', 70)
            ->first();

            if($this->submission_type == 'onlinetext'){

                $submission->url()->updateOrCreate(
                    [
                        'assignment' => $this->assignment->id
                    ],
                    [
                        'onlinetext' => $this->url
                    ]
                );
                
            } else {
                $this->insertFile($context->id, $submission->id);
            }


            $submission->update([
                'status' => 'submitted',
            ]);

            DB::commit();

            GlobalHelper::rebuildCourseCache($this->assignment->course);


        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info($th);
            throw $th;
        }

    }

    public function deleteOldFile($idx){

        DB::beginTransaction();

        try {
            $file = $this->oldFiles[$idx];
            if(count($this->oldFiles) == 1){
                $this->assignmentSubmission->update([
                    'status' => 'new',
                    'timemodified' => time()
                ]);
            }
            ResourceFile::destroy($file->id);
            array_splice($this->oldFiles, $idx, 1);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function insertFile($ctxid, $itemid){

        foreach($this->files as $files){

            ResourceFile::create([
                'contenthash' => $files[0]->contenthash,
                'pathnamehash' => GlobalHelper::get_pathname_hash($files[0]->contextid, 'assignsubmission_file', 'submission_files', $files[0]->itemid, '/', $files[0]->filename),
                'contextid' => $ctxid,
                'component' => 'assignsubmission_file',
                'filearea' => 'submission_files',
                'itemid' => $itemid,
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
                'component' => 'assignsubmission_file',
                'filearea' => 'submission_files',
                'contextid' => $ctxid,
                'contenthash' => $files[1]->contenthash,
                'pathnamehash' => GlobalHelper::get_pathname_hash($ctxid, 'assignsubmission_file', 'submission_files', $files[1]->itemid, '/', $files[1]->filename),
                'itemid' => $itemid,
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

}
