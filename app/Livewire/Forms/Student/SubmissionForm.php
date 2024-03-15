<?php

namespace App\Livewire\Forms\Student;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SubmissionForm extends Form
{
    public Assignment $assignment;
    public ?AssignmentSubmission $assignmentSubmission;

    public $file;

    public $files;

    public $oldFiles;

    public $url;

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

            $instance->url()->create([
                'url' => $this->url
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

}
