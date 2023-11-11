<?php

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};    
use App\Models\CourseModule;
use App\Models\Course;
use App\Models\Assign;
use App\Models\AssignPluginConfig;
use App\Models\Role;
use App\Models\Enrol;
use Illuminate\Support\Facades\DB;

middleware(['auth']);
name('courseModule');
state(['courseModule', 'assign', 'assignType', 'participantCount', 'submittedCount']);

mount(function ($cmid){
    $this->courseModule = CourseModule::find($cmid);
    $this->assign = Assign::find($this->courseModule->instance);
    $this->assignPlugin = AssignPluginConfig::where('assignment', $this->assign->id)->get();
    $assignPlugin = AssignPluginConfig::where('assignment', $this->assign->id)->get();
    $this->getAssignmentPlugin($this->assign->id);
    $this->getParticipantCount($this->assign->course);
    Log::debug($this->assign->submission);

});

$getAssignmentPlugin = function ($courseid){
    
    $submissionType = AssignPluginConfig::where('assignment', $courseid)
                    ->where('plugin', 'file')
                    ->where('name', 'enabled')
                    ->where('value', '1')
                    ->get();
    
    if(count($submissionType) == 0){
        $this->assignType = 'Text Daring';
        return;
    } 

    $this->assignType = 'File';

};  

$getParticipantCount = function ($courseid){
    $studentRole = Role::where('shortname', 'student')->first();

    $data = DB::table('mdl_enrol')
                ->where('mdl_enrol.courseid', $courseid)
                ->where('mdl_enrol.roleid', $studentRole->id)
                ->where('mdl_user_enrolments.userid', '!=',auth()->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                ->count();

    $this->participantCount = $data;

};

$getSubmittedCount = function ($assignmentid){

    // $data = 


    // $this->participantCount = $data->count;

};

?>

<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto">
        <div class=" bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button path="/" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> Pemgrograman Web 1 - Kelas A <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Detail Penugasan - {{ $courseModule->sectionDetail->name }}</span></p>
            <div class="flex justify-between items-center">
                <h1 class="text-[#121212] text-xl font-semibold" >Detail Penugasan - {{ $courseModule->sectionDetail->name }}</h1>
                <button class="btn btn-primary" >Edit Tugas</button>
            </div>
        </div>

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $assign->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $assign->intro !!}</p>
                <div class="columns-4 mt-4 font-medium text-sm" >
                    <p class="text-grey-500" >Tenggat Waktu</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ Carbon\Carbon::parse($assign->duedate)->format('d F Y, H:i') }}</p>
                    <p class="text-grey-500" >Peserta</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ $participantCount }}</p>
                </div>
                <div class="columns-4 mt-3 font-medium text-sm" >
                    <p class="text-grey-500" >Waktu Tersisa</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span>  </p>
                    <p class="text-grey-500" >Terkumpul</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ 'asdf' }}</p>
                </div>
                <div class="columns-4 mt-3 font-medium text-sm" >
                    <p class="text-grey-500" >Jenis Pengiriman</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ $this->assignType }}</p>
                    <p class="text-grey-500" >Belum dinilai</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> </p>
                </div>
            </div>
        </div>



    </div>
    @endvolt
</x-layouts.app>