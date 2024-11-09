<?php

use function Livewire\Volt\{state, mount, on};
use App\Models\{
    GradeItem,
    AssignGrade,
    Course,
    GradeGrades,
    CourseModule,
    CourseSection,
    Assignment,
    AssignmentSubmission,
    Context,
    Role,
    User,
};

state([
    'course', 
    'section', 
    'assignment', 
    'assignmentSubmission', 
    'student',
    'type',
    'files',
    'url',
    'grade',
]);

mount(function(Course $course, CourseSection $section, Assignment $assignment, User $student, AssignmentSubmission $assignmentSubmission, CourseModule $courseModule){
    $this->assignment = $assignment;
    $this->course = $course;
    $this->section = $section;
    $this->assignmentSubmission = $assignmentSubmission;
    $this->student = $student;

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
        $this->type = 'onlinetext';
    } 

    if($file_plugin){
        $this->type = 'file';
        $ctx_id = Context::where('contextlevel', 70)->where('instanceid', $courseModule->id)->first('id')->id;

        $files = DB::connection('moodle_mysql')->table('mdl_files')
        ->where('contextid', $ctx_id)
        ->where('component', 'assignsubmission_file')
        ->where('filearea', 'submission_files')
        ->where('itemid', $assignmentSubmission->id)
        ->where('filename', '!=', '.')
        ->orderBy('id')
        ->get();

        $this->files = $files->map(function($e){
            $e->id = $e->id;
            $e->name = $e->filename;
            $e->file = "/preview/file/$e->id/$e->filename";
            $e->size = number_format($e->filesize / 1024 / 1024, 2) . ' MB';
            $e->itemid = $e->itemid;
            return $e;
        })->toArray();   
    }

    $this->grade = AssignGrade::where('assignment', $assignment->id)
    ->where('userid', $student->id)->first('grade')->grade;    

    $this->grade = number_format($this->grade, 2);

    // if($this->type == 'file'){
    //     $this->files = $assignmentSubmission->files;
    // }
    // if($this->type == 'onlinetext'){
    //     $this->url = $assignmentSubmission->url->url;
    // }
});

$submit_grade = function (){
    
    $this->validate(['grade' => 'required']);

    DB::beginTransaction();

    try {
        AssignGrade::updateOrCreate(
            [
                'assignment' => $this->assignment->id,
                'userid' => $this->student->id,
            ],
            [
                'timemodified' => time(),
                'grade' => $this->grade,
                'grader' => auth()->user()->id,
                'timecreated' => time(),
            ]
        );

        $gradeItem = GradeItem::where('iteminstance', $this->assignment->id)
        ->where('itemmodule', 'assign')
        ->where('courseid', $this->course->id)
        ->first();

        $gradeGrades = GradeGrades::where('itemid', $gradeItem->id)
        ->where('userid', $this->student->id)
        ->update([
            'rawgrade' => $this->grade,
            'finalgrade' => $this->grade,
            'usermodified' => auth()->user()->id,
            'timemodified' => time(),
        ]);

        DB::commit();

        $this->grade = AssignGrade::where('assignment', $this->assignment->id)
        ->where('userid', $this->student->id)->first('grade')->grade;    

        $this->grade = number_format($this->grade, 2);

        $this->dispatch('notify', 'success', 'Penilaian berhasil disimpan');
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::info($th->getMessage());
        $this->dispatch('notify', 'error', $th->getMessage());
    }

}

?>

<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto">
        <div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button wire:navigate.hover path="/" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="" >Detail Tugas - {{ $section->name }}</span> <span class="mx-2  text-[9px]" > >> </span> <span class="text-[#121212]" >{{ $student->firstname . ' ' . $student->lastname }} - Nilai</span> </p>
            <h1 class="text-[#121212] text-xl font-semibold" >{{ $student->firstname . ' ' . $student->lastname }} - Nilai</h1>
        </div>

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <div class="flex items-center" >
                    <img src="{{ asset('assets/images/avatar.webp') }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                    <div>
                        <p class="mb-1">{{ $student->firstname . ' ' .$student->lastname }}</p>
                        <span class="text-grey-500 " >{{ $student->username }}</span>
                    </div>
                </div>
                <div class="flex mt-4 font-medium text-sm" >
                    <p class="text-grey-500 w-[250px]" >Waktu Pengumpulan</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ Carbon\Carbon::parse($assignmentSubmission->created_at)->translatedFormat('d F Y, H:i') }}</p>
                </div>
                <div class="flex mt-4 font-medium text-sm" >
                    <p class="text-grey-500 w-[250px]" >Status</p>
                    <p class="text-[#121212]" >
                        <span class="mr-2" >:</span> 
                        @php
                            $sub_time = \Carbon\Carbon::parse($assignmentSubmission->created_at);
                            $assign_time = \Carbon\Carbon::parse($assignment->due_date);
                        @endphp
                        @if ($sub_time->gt($assign_time))
                        <p class="chip late text-center px-3 py-1 text-xs w-fit font-medium rounded-xl">Terlambat Dikumpulkan</p>
                        @else
                        <p class="chip attend text-center px-3 py-1 text-xs w-fit font-medium rounded-xl">Dikumpulkan</p>
                        @endif
                    </p>
                </div>
                <div class="flex mt-4 font-medium text-sm" >
                    <p class="text-grey-500 w-[250px]" >Nilai</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> <span class="text-primary" >{{  !is_null($grade) ? str_replace('.',',',number_format($grade, 2)) : '0,00' }}</span> dari 100,00</p>
                </div>
            </div>


            <form wire:submit="submit_grade">
                <div class="bg-white p-5 rounded-xl mt-6" >
                    @if ($type == 'file')
                    <p class="text-grey-800 font-semibold pb-2">File yang dikumpulkan</p>
                    <div class="space-y-4" >
                        @foreach ($files as $file)
                        <a target="blank" href="{{ $file->file }}" class="mb-2 py-4 px-6 flex items-center border rounded-xl border-grey-300" >
                            <img src="{{ asset('assets/icons/pdf.svg') }}" alt="">
                            <p class="text-[#121212] ml-3 text-sm" >{{ $file->name }}</p>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <p class="text-grey-800 font-semibold pb-2">Url yang dikumpulkan</p>
                    <a class="underline text-blue-600 hover:text-blue-800" target="blank" href="{{ $url }}">{{ $url }}</a>
                    @endif
                    <div class="h-4" ></div>
                    <label for="grade" >
                        <span class="block label text-gray-600 text-[12px] mb-1" >Nilai</span>
                        <input wire:model="grade" type="text" id="grade" placeholder="Masukkan Nilai"  class="text-field max-w-[320px]">
                        @error('grade')
                            <span class="text-error mt-2 text-sm block" >Nilai harus diisi</span>
                        @enderror
                    </label>
                </div>
    
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button type="submit" >
                        Submit
                    </x-button>
                    <x-button @click="$store.alert.cancel = true" variant="outlined" >
                        Batal
                    </x-button>
                </div>
            </form>

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