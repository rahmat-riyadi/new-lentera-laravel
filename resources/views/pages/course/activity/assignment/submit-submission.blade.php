<?php

use function Livewire\Volt\{state, mount, on, updated, usesFileUploads, form};
use App\Livewire\Forms\Student\SubmissionForm;
use App\Models\{
    Course,
    CourseSection,
    Assignment,
    User,
    Context,
    Role,
    CourseModule,
    AssignmentSubmission,
    AssignmentSubmissionFile
};

state([
    'course', 
    'section', 
    'assignment', 
    'role', 
    'courseModule',
    'type',
]);

form(SubmissionForm::class);
usesFileUploads();

mount(function (Course $course, CourseSection $section, Assignment $assignment, CourseModule $courseModule){

    $this->type = $assignment->configs()->where('name', 'type')->first()->value;

    $submission = AssignmentSubmission::where('student_id', auth()->user()->id)
    ->where('assignment_id', $assignment->id)
    ->first();

    $this->form->assignment = $assignment;

    if($submission){
        $this->form->setSubmission($submission);
        if($this->type == 'file'){
            $this->form->oldFiles = $submission->files;
        }
        if($this->type == 'onlinetext'){
            $this->form->url = $submission->url->url;
        }
    }

    $this->course = $course;
    $this->section = $section;
    $this->courseModule = $courseModule;
});

updated([
    'form.file' => function (){
        foreach ($this->form->file as $file) {
            $this->form->files[] = $file;
        }
    }
]);

$deleteFile = function ($id){
  array_splice($this->form->files, $id, 1);
};

$deleteOldFile = function ($file){
    try {
        AssignmentSubmissionFile::destroy($file['id']);
        Storage::delete($file['path']);
        $this->form->oldFiles = $this->form->assignmentSubmission->files;
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
};

$submit_file = function (){

    try {
        $this->form->submitFiles();
        session()->flash('success', 'Tugas berhasil dikumpul');
        $this->redirect("/course/{$this->course->shortname}/activity/assignment/detail/{$this->courseModule->id}", navigate: true);
    } catch (\Throwable $th) {
        $this->dispatch('notify', 'error', $th->getMessage());
    }

};

$submit_url = function (){

    $this->form->validate([
        'url' => 'required'
    ]);

    try {
        $this->form->submitUrl();
        session()->flash('success', 'Tugas berhasil dikumpul');
        $this->redirect("/course/{$this->course->shortname}/activity/assignment/detail/{$this->courseModule->id}", navigate: true);
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
        $this->dispatch('notify', 'error', $th->getMessage());
    }

};

$submit = function (){
    if($this->type == 'file')
        $this->submit_file();
    else 
        $this->submit_url();
}


?>

<x-layouts.app>
    @volt
    <div class="h-screen md:h-full overflow-y-auto" >
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Detail Penugasan"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                @if ($type == 'file')
                    <span class="label inline-block mb-2" >Masukkan File</span>
                    <label for="file" class="bg-grey-100 flex justify-center py-6 rounded-md cursor-pointer" >
                        <div class="flex items-center px-3" >
                            <img src="{{ asset('assets/icons/upload-file.svg') }}" alt=""/>
                            <div class="ml-4" >
                                <p class="font-medium mb-1" >Drag and drop a file here or click</p>
                                <p class="text-grey-500 text-sm" >(pdf, docx, pptx, xlsx)</p>
                            </div>
                        </div>
                        <input multiple wire:model.live="form.file" name="files" id="file" type="file" class="invisible absolute" />
                    </label>
                    @error('files')
                    <span class="text-error mt-3 text-sm" >{{ $message ?? 's' }}</span>
                    @enderror
                    <div class="flex flex-col mt-4" >
                        @foreach ($form->oldFiles ?? [] as $i => $item)
                        <div class="flex items-center px-4 py-2 bg-grey-100 rounded-lg mb-3" >
                            <img class="w-7 mr-4" src="{{ asset('assets/icons/pdf.svg') }}" >
                            <div>
                                <p class="font-semibold text-sm mb-[2px]" >{{ $item->name}}</p>
                                <p class="text-xs text-grey-500"  >{{ $item->size }}</p>
                            </div>
                            <span class="ml-auto cursor-pointer" wire:confirm="Yakin ingin hapus file?" wire:click="deleteOldFile({{ $item }})" >X</span>
                        </div>
                        @endforeach
                        @foreach ($form->files ?? [] as $i => $item)
                        <div class="flex items-center px-4 py-2 bg-grey-100 rounded-lg mb-3" >
                            <img class="w-7 mr-4" src="{{ asset('assets/icons/pdf.svg') }}" >
                            <div>
                                <p class="font-semibold text-sm mb-[2px]" >{{ $item->getClientOriginalName() }}</p>
                                <p class="text-xs text-grey-500"  >{{ $item->getSize() }}</p>
                            </div>
                            <span class="ml-auto cursor-pointer" wire:confirm="Yakin ingin hapus file?" wire:click="deleteFile('{{ $i }}')" >X</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <label for="urlname" class="" >
                        <span class="block label text-gray-600 text-[12px] mb-1" >Url</span>
                        <input wire:model="form.url" type="url" id="urlname" placeholder="Masukkan Url"  class="text-field">
                        @error('form.url')
                        <span class="text-error mt-3 text-sm" >{{ 'Url tidak boleh kosong' }}</span>
                        @enderror
                    </label>
                    <div class="h-3" ></div>
                @endif
                <div class="flex justify-end gap-3 mt-2" >
                    <a wire:navigate.hover href="/course/{{ $course->shortname }}/activity/assignment/detail/{{ $courseModule->id }}" class="btn btn-light inline-block grow text-center md:block md:grow-0" >
                        Batal
                    </a>
                    <x-button wire:click="submit" class="inline-block text-center md:block md:grow-0" >
                        Simpan Penugasan
                    </x-button>
                </div>
            </div>
        </div>

        <x-toast/>

    </div>

    @script
    <script>

        Livewire.on('notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })
        
    </script>
    @endscript

    @endvolt
</x-layouts.app>