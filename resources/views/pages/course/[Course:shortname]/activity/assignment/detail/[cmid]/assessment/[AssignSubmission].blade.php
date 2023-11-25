<?php

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount, on};    
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\AssignSubmission;
use App\Models\AssignGrade;
use App\Models\AssignPluginConfig;
use App\Models\Url;

middleware(['auth']);
// name('courseModule');

state(['courseModule', 'assignSubmission', 'assignGrade', 'assignPluginConfig', 'is_onlinetext', 'value', 'course']);

mount(function ($cmid, Course $course, AssignSubmission $assignSubmission){
    $this->course = $course;
    $this->courseModule = CourseModule::find($cmid);
    $this->assignSubmission = $assignSubmission;
    $this->assignGrade = AssignGrade::where('assignment', $assignSubmission->assignment)
                        ->where('userid', $assignSubmission->userid)
                        ->first();
    $this->assignPluginConfig = AssignPluginConfig::where('assignment', $assignSubmission->assignment)->get();
    $assignPlugin = AssignPluginConfig::where('assignment', $assignSubmission->assignment) 
                    ->where('plugin', 'onlinetext')
                    ->where('name', 'enabled')
                    ->where('value', '1')
                    ->first();

    $this->is_onlinetext = !empty($assignPlugin);

    if(!empty($this->assignGrade)){
        $this->value = number_format($this->assignGrade->grade, 2);
    }

});

$fetchGrade = function (){
    $this->assignGrade = AssignGrade::where('assignment', $this->assignSubmission->assignment)
    ->where('userid', $this->assignSubmission->userid)
    ->first();
};

on(['refresh' => '$refresh']);

$submit = function (){

    try {

        $data = AssignGrade::where('assignment', $this->assignSubmission->assignment)
                ->where('userid', $this->assignSubmission->userid)
                ->first();

        if(empty($data)){
            AssignGrade::create([
                'assignment', $this->assignSubmission->assignment,
                'userid', $this->assignSubmission->userid,
                'timecreated' => time(),
                'timemodified' => time(),
                'grade' => $this->value,
                'grader' => auth()->user()->id
            ]);
        } else {
            $data->update([
                'timemodified' => time(),
                'grade' => $this->value
            ]);
        }

        Log::debug('succeess');
        
        $this->dispatch('refresh');
        
    } catch (\Throwable $th) {
        Log::debug($th->getMessage());
    }
};

$back = function (){
  $this->redirect("/course/{$this->course->shortname}/activity/assignment/{$this->courseModule->id}");
};


?>

<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto">
        <div class=" bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button path="/" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> Pemgrograman Web 1 - Kelas A <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Detail Penugasan - {{ $courseModule->sectionDetail->name }}</span></p>
            <div class="flex items-center">
                <h1 class="text-[#121212] text-xl font-semibold" >Detail Penugasan - {{ $courseModule->sectionDetail->name }}</h1>
                <p class="ml-auto font-medium text-sm mr-3" >1 - 20</p>
                <a href="/" class="text flex items-center text-primary font-semibold" >Selanjutnya <ArrowSvg class="fill-primary -rotate-90 ml-1" /></a>
            </div>
        </div>

        <form wire:submit="submit">
            <div class="p-8">
                <div class="bg-white p-5 rounded-xl">
                    <div class="flex items-center" >
                        <img src="/images/avatar.jpg" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                        <div>
                            <p class="mb-1">{{ $assignSubmission->user->firstname . ' ' .$assignSubmission->user->lastname }}</p>
                            <span class="text-grey-500 " >{{ $assignSubmission->user->username }}</span>
                        </div>
                    </div>
                    <div class="flex mt-4 font-medium text-sm" >
                        <p class="text-grey-500 w-[250px]" >Waktu Pengumpulan</p>
                        <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ Carbon\Carbon::parse($assignSubmission->assign->duedate)->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                    <div class="flex mt-4 font-medium text-sm" >
                        <p class="text-grey-500 w-[250px]" >Status</p>
                        <p class="text-[#121212]" ><span class="mr-2" >:</span> <span class="chip attend text-center px-3 text-xs w-fit font-medium rounded-xl">{{ $assignSubmission->status == 'submitted' ? 'Selesai Mengerjakan' : '' }}</span> </p>
                    </div>
                    <div class="flex mt-4 font-medium text-sm" >
                        <p class="text-grey-500 w-[250px]" >Nilai</p>
                        <p class="text-[#121212]" ><span class="mr-2" >:</span> <span class="text-primary" >{{ number_format($assignGrade->grade, 2, ',') ?? '0,00' }}</span> dari 100,00</p>
                    </div>
                </div>
    
                <div class="bg-white p-5 rounded-xl mt-6">
                    <p class="text-grey-800 font-semibold pb-2">File yang dikumpulkan</p>
                    @if ($is_onlinetext)
                    <a href="javascript:;" class="mb-2 py-4 px-6 flex items-center border rounded-xl border-grey-300" >
                        <img src="{{ asset('assets/icons/url.svg') }}" alt="">
                        <p class="text-[#121212] ml-3 text-sm" >{!! $assignSubmission->urlSubmission->onlinetext !!}</p>
                    </a>
                    @else
                    <a href="/" class="mb-2 py-4 px-6 flex items-center border rounded-xl border-grey-300" >
                        <img src="/icons/pdf.svg" alt="">
                        <p class="text-[#121212] ml-3 text-sm" >Tugas 1_Rahmat Riyadi</p>
                    </a>
                    @endif
                    <label for="value" >
                        <span class="block label text-gray-600 text-[12px] mb-1 mt-4" >Nilai</span>
                        <div class="text-field flex w-1/3" >
                            <input type="text" wire:model.live="value" id="value" placeholder="Masukkan Nilai"  class="text-field-base peer grow">
                        </div>
                    </label>
                </div>
    
                <div class="flex justify-end gap-3 mt-7" >
                    <x-button type="submit" >
                        <span wire:loading wire:target="submit" >
                            <x-loading/>
                        </span>
                        <span wire:loading.remove wire:target="submit" >Simpan</span>
                    </x-button>
                    <a 
                        wire:navigate
                        href="/course/{{ $course->shortname }}/activity/assignment/detail/{{ $courseModule->id }}"
                        class="
                            btn-outlined
                            flex 
                            justify-center 
                            items-center
                            relative
                        " 
                    >
                        Batal
                    </a>
                </div>
    
            </div>
        </form>


    </div>
    @endvolt

</x-layouts.app>