
<?php

use function Livewire\Volt\{state, mount, on, updated};
use App\Helpers\GlobalHelper;
use App\Exports\GradeExport;
use App\Helpers\CourseHelper;
use App\Models\{
    Module,
    CourseModule,
    Course,
    CourseSection,
    Url,
    Resource,
    Assign,
    Context,
    Role,
    User,
    Quiz,
    Assignment,
    StudentQuiz,
    AssignmentSubmission,
    Attendance,
    StudentAttendance,
};

state([
    'course', 
    'role', 
]);

mount(function(Course $course){
    Log::info($co);
    $ctx = Context::where('contextlevel', 50)->where('instanceid', $course->id)->first();
    Log::info($ctx);
    $data = DB::connection('moodle_mysql')->table('mdl_role_assignments as ra')
    ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
    ->where('ra.contextid', $ctx->id)
    ->where('ra.userid', auth()->user()->id)
    ->select(
        'r.shortname as role',
    )
    ->first();

    $this->role = $data->role;
    $this->course = $course;


});



?>

<x-layouts.app>
    @volt
    <div class="overflow-y-auto h-full pb-3" >
        <div class=" bg-white course-page-header px-8 pt-8 font-main flex flex-col" >
            <p class="text-sm text-[#656A7B] font-[400] flex items-center" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> <span class="text-[#121212]" > Pemgrograman Web 1 - Kelas A</span></p>
            <h1 class="text-[#121212] text-2xl font-semibold mt-8" >{{ $course->fullname }} - Kelas A</h1>
            <div class="flex items-center mt-3" >
                <p class="text-lg" >Teknik Informatika</p>
                @if ($role != 'student')
                <x-button 
                    class="ml-auto"
                    wire:click="add_section"
                >
                    <svg class="w-[16.5px] fill-white mr-2 " width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.5 7.5H10.5V1.5C10.5 1.10218 10.342 0.720645 10.0607 0.43934C9.77936 0.158036 9.39782 0 9 0C8.60218 0 8.22064 0.158036 7.93934 0.43934C7.65804 0.720645 7.5 1.10218 7.5 1.5V7.5H1.5C1.10218 7.5 0.720645 7.65804 0.43934 7.93934C0.158036 8.22064 0 8.60218 0 9C0 9.39782 0.158036 9.77936 0.43934 10.0607C0.720645 10.342 1.10218 10.5 1.5 10.5H7.5V16.5C7.5 16.8978 7.65804 17.2794 7.93934 17.5607C8.22064 17.842 8.60218 18 9 18C9.39782 18 9.77936 17.842 10.0607 17.5607C10.342 17.2794 10.5 16.8978 10.5 16.5V10.5H16.5C16.8978 10.5 17.2794 10.342 17.5607 10.0607C17.842 9.77936 18 9.39782 18 9C18 8.60218 17.842 8.22064 17.5607 7.93934C17.2794 7.65804 16.8978 7.5 16.5 7.5Z"/>
                    </svg>
                    <span>Tambah Pertemuan</span>
                </x-button>
                @endif
                {{-- <button class="btn-icon-light ml-3 hidden md:flex" >
                    <VerticalMoreSvg/>
                </button> --}}
            </div>
            @if ($role != 'student')
            <div class="flex mt-4 gap-x-6" >
                <button  @click="tab = 'proggress'" :class=" tab == 'proggress' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all " >
                    <template x-if="tab == 'proggress'" >
                        <x-icons.chartbar  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'proggress'" >
                        <x-icons.chartbar  class=" fill-grey-400 w-5 transition-all" />
                    </template>
                    <p :class="tab == 'proggress' ? 'text-black' : 'text-grey-400' " class="font-medium  ml-2 text-sm transition-all" >Progres</p>
                </button>
                <button @click="tab = 'value'" :class=" tab == 'value' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all" >
                    <template x-if="tab == 'value'" >
                        <x-icons.coin  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'value'" >
                        <x-icons.coin class="fill-grey-400 transition-all w-5 " />
                    </template>
                    <p :class="tab == 'value' ? 'text-black' : 'text-grey-400' " class="font-medium  transition-all ml-2 text-sm " >Nilai</p>
                </button>
                <button @click="tab = 'participants'" :class=" tab == 'participants' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all" >
                    <template x-if="tab == 'participants'" >
                        <x-icons.user-fill  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'participants'" >
                        <x-icons.user-fill class="fill-grey-400 transition-all w-5 " />
                    </template>
                    <p :class="tab == 'participants' ? 'text-black' : 'text-grey-400' " class="font-mediumtransition-all ml-2 text-sm" >Peserta</p>
                </button>
                <button @click="tab = 'import'" :class=" tab == 'import' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all" >
                    <template x-if="tab == 'import'" >
                        <x-icons.upload  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'import'" >
                        <x-icons.upload class="fill-grey-400 transition-all w-5 " />
                    </template>
                    <p :class="tab == 'import' ? 'text-black' : 'text-grey-400' " class="font-mediumtransition-all ml-2 text-sm" >Import Kelas</p>
                </button>
            </div>
            @else
            <div class="h-4" ></div>
            @endif
        </div>

        <div class="px-8">
            {{ $slot }}
        </div>

    </div>
    @endvolt
</x-layouts.app>