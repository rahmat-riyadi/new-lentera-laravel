<?php

use function Livewire\Volt\{state, mount, on};
use App\Models\Enrol;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Assignment;
use Carbon\Carbon;
state(['curr_tab', 'courses', 'showed_courses', 'colors', 'activities']);

mount(function () {
    $this->colors = ['#36B37E', '#00AED6', '#93328E'];
    $this->curr_tab = 'current';
    // $this->get_courses();
    // $this->change_courses('current');
    // $time = time();
    // $courseids = Course::
    // whereIn('mdl_course.id', function($q) use ($time){
    //     $q->select('e.courseid')
    //     ->from('mdl_enrol as e')
    //     ->join('mdl_user_enrolments as ue', function ($join) {
    //         $join->on('ue.enrolid', '=', 'e.id')
    //             ->where('ue.userid', '=', auth()->user()->id);
    //     })
    //     ->join('mdl_course as c', 'c.id', '=', 'e.courseid')
    //     ->where('ue.status', '=', '0')
    //     ->where('e.status', '=', '0')
    //     ->where('ue.timestart', '<=', $time)
    //     ->where(function ($query) use ($time) {
    //         $query->where('ue.timeend', '=', 0)
    //                 ->orWhere('ue.timeend', '<', $time);
    //     });
    // })
    // ->pluck('id');
    
    // $quiz = Quiz::whereIn('course_id', $courseids)
    // ->join('moodle402.mdl_course as c', 'c.id', '=', 'quizzes.course_id')
    // ->whereNotNull('activity_remember')
    // ->where('activity_remember', '<=', \Carbon\Carbon::now())
    // ->select(
    //     'quizzes.id',
    //     'quizzes.name',
    //     'c.fullname as course',
    //     'quizzes.due_date',
    //     'quizzes.activity_remember',
    // )
    // ->get();

    // $quiz = $quiz->map(function($e){
    //     $e->type = 'quiz';
    //     return $e;
    // });

    // $assignment = collect([]);

    // // $assignment = Assignment::whereIn('course', $courseids)
    // // ->join('mdl_course as c', 'c.id', '=', 'mdl_assign.course')
    // // ->whereNotNull('activity_remember')
    // // // ->where('activity_remember', '<', \Carbon\Carbon::now()->format('Y-m-d'))
    // // ->select(
    // //     'assignments.id',
    // //     'c.fullname as course',
    // //     'assignments.name',
    // //     'assignments.due_date',
    // //     'assignments.activity_remember',
    // // )
    // // ->get();

    // $assignment = $assignment->map(function($e){
    //     $e->type = 'assignment';
    //     return $e;
    // });

    // $this->activities = collect($assignment->merge($quiz))->sortBy('due_date');

});

$change_tab = function ($val){
    $this->curr_tab = $val;
    $this->change_courses($val);
};

$change_courses = function ($mode){
    Log::info(time());
    $this->showed_courses = $this->courses->filter(function($e) use ($mode) {
        if($mode == 'past'){
            return $e->enddate < time() && $e->enddate != 0;
        }

        if($mode == 'current'){
            // return !($e->startdate > time()) && !($e->enddate < time() && $e->enddate != 0);
            return (time() >= $e->startdate) && ( time() <= $e->enddate || $e->enddate == 0 );
        }

        if($mode == 'starred'){
            return $e->fav_id;
        }

        return true;
        
    });
};

$get_courses = function (){
    $uid = auth()->user()->id;
    $time = time();
    $this->courses = Course::with('categoryInfo:id,name')
    ->leftJoin('mdl_favourite as f', function($q){
        $q->on('f.itemid', '=', 'mdl_course.id')
        ->where('f.itemtype', 'courses')
        ->where('f.userid', auth()->user()->id);
    })
    ->whereIn('mdl_course.id', function($q) use ($time){
        $q->select('e.courseid')
        ->from('mdl_enrol as e')
        ->join('mdl_user_enrolments as ue', function ($join) {
            $join->on('ue.enrolid', '=', 'e.id')
                ->where('ue.userid', '=', auth()->user()->id);
        })
        ->join('mdl_course as c', 'c.id', '=', 'e.courseid')
        ->where('ue.status', '=', '0')
        ->where('e.status', '=', '0')
        ->where('ue.timestart', '<=', $time)
        ->where(function ($query) use ($time) {
            $query->where('ue.timeend', '=', 0)
                    ->orWhere('ue.timeend', '>', $time);
        });
    })
    ->select(
        'mdl_course.id',
        'mdl_course.category',
        'mdl_course.fullname',
        'mdl_course.shortname',
        'mdl_course.startdate',
        'mdl_course.enddate',
        'f.id as fav_id'
    )
    ->get();
};

?>

<x-layouts.app>
    @volt
    <div class="grid grid-cols-12 p-7 gap-x-7 no-scrollbar h-full grow overflow-y-auto" >
        {{-- <div class="order-2 md:order-1 col-span-full lg:col-span-9">
            <div class="bg-white p-4 rounded-xl mb-7" >
                <div class="flex items-center mb-3" >
                    <p class="font-bold mr-auto" >Pengingat Aktivitas {{ asset('moodledir') }}</p>
                    <div class="button flex items-center">
                        <button class="rotate-180 prev-carr-btn mr-2" >
                            <img class="w-[25px]"  src="{{ asset('/assets/icons/arrow-right.svg') }}" alt="">
                        </button>
                        <button class="next-carr-btn" >
                            <img class="w-[25px]"  src="{{ asset('/assets/icons/arrow-right.svg') }}" alt="">
                        </button>
                    </div>
                </div>
                <div wire:ignore class="swiper">
                    <div class="swiper-wrapper">
                        @foreach ($activities as $activity)
                        <div class="swiper-slide">
                            <div class="border border-grey-300 p-4 rounded-lg">
                                @if ($activity->type == 'quiz')
                                <img src="{{ asset('assets/icons/kuis.svg') }}" alt="" class="w-8 mb-3">
                                @else
                                <img src="{{ asset('assets/icons/penugasan.svg') }}" alt="" class="w-8 mb-3">
                                @endif
                                <p class="font-semibold text-sm mb-1" >{{ $activity->name }}</p>
                                <p class="text-[13px] mb-4" >{{ $activity->course }}</p>
                                <div class="flex text-[11px] text-grey-600">
                                    @php
                                        $carbon = \Carbon\Carbon::parse($activity->due_date);
                                        $date = $carbon->translatedFormat('d F Y');
                                        $time = $carbon->translatedFormat('H:i');
                                    @endphp
                                    <p class="flex items-center mr-4" >
                                        <span class="mr-[2px]" >
                                            <img class="w-4" src="{{ asset('/assets/icons/calendar-linear.svg') }}" >
                                        </span> 
                                        {{ $date }}
                                    </p>
                                    <p class="flex items-center" >
                                        <span class="mr-[2px]" >
                                            <img class="w-4" src="{{ asset('/assets/icons/clock-linear.svg') }}" >
                                        </span> 
                                        {{ $time }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl" >
                <p class="font-semibold text-lg text-[#121212] mb-4" >Mata Kuliah Baru diakses</p>
                <div class="grid gap-5 sx:grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3" >
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl mt-7" >
                <p class="font-semibold text-lg text-[#121212] mb-4" >Mata Kuliah</p>
                <div class="mb-4" >
                    <button wire:click="change_tab('all')" class="mr-1 btn-tabs {{ $curr_tab == 'all' ? 'active' : '' }}" >Semua</button>
                    <button wire:click="change_tab('current')" class="mr-1 btn-tabs {{ $curr_tab == 'current' ? 'active' : '' }}" >Sedang berlangsung</button>
                    <button wire:click="change_tab('past')" class="mr-1 btn-tabs {{ $curr_tab == 'past' ? 'active' : '' }}" >Masa Lalu</button>
                    <button wire:click="change_tab('starred')" class="mr-1 btn-tabs {{ $curr_tab == 'starred' ? 'active' : '' }}" >Berbintang</button>
                </div>
                <div class="grid gap-4 sx:grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3" >
                    @foreach ($showed_courses as $i => $course)
                    <x-course-card
                        idx="{{ ($i%6)+1 }}"
                        studyProgram="{{ $course->categoryInfo->name }}"
                        course="{{ $course->fullname }}"
                    />
                    @endforeach
                </div>
            </div>
            <div class="h-10" ></div>
        </div>
        <div class="order-1 md:order-2 col-span-full lg:col-span-3" >
            <div class="flex justify-between items-center mb-3">
                <p class="body-1 text-lg md:text-base font-semibold" >Pengumuman</p>
                <button class="text-sm text-primary-dark" >Lihat Semua</button>
            </div>
            <div class="bg-primary-light p-3 rounded-xl" >
                <p class="text-xs mb-1" >12 Maret 2023, 10.24</p>
                <p class="font-semibold leading-5 md:text-sm mb-3 md:mb-2" >Semua dosen harap segera memasukkan materi pada mata kuliah yang diajarkan</p>
                <p class="text-sm font-medium md:font-normal md:text-xs" >Dosen diharap memasukkan materi disetiap pertemuan sebelum perkuliahan berlangsung. Agar mahasiswa dapat melihat dan mempelajari terlebih dahulu.</p>
            </div>
            <div class="hidden sm:block" >
                <p class="body-1 font-semibold mt-4 mb-3" >Aktivitas Akan Datang</p>
                <div class="space-y-4" >
                    @foreach ($activities as $i => $activity)
                    <div  class="border-l-[5px] rounded-r-lg bg-white py-3 px-4 shadow-md"  style="border-color: {{ $colors[$i%3] }};">
                        <p class="font-semibold text-sm mb-1" >{{ $activity->name }}</p>
                        <p class="text-[13px] mb-4" >{{ $activity->course }}</p>
                        <div class="flex text-[11px] text-grey-600">
                            @php
                                $carbon = \Carbon\Carbon::parse($activity->due_date);
                                $date = $carbon->translatedFormat('d F Y');
                                $time = $carbon->translatedFormat('H:i');
                            @endphp
                            <p class="flex items-center mr-4" >
                                <span class="mr-[2px]" >
                                    <img class="w-4" src="{{ asset('/assets/icons/calendar-linear.svg') }}" >
                                </span> 
                                {{ $date }}
                            </p>
                            <p class="flex items-center" >
                                <span class="mr-[2px]" >
                                    <img class="w-4" src="{{ asset('/assets/icons/clock-linear.svg') }}" >
                                </span> 
                                {{ $time }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div> --}}
    </div>

    @assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    @endassets

    @script
    <script>
        const swiper = new Swiper('.swiper',{
            slidesPerView: 1.3,
            spaceBetween: 20,
            navigation: {
                nextEl: '.next-carr-btn',
                prevEl: '.prev-carr-btn',
            },
            breakpoints: {
                480: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                1150: {
                    slidesPerView: 3,
                    spaceBetween: 20
                },
            }
        });
    </script>
    @endscript

    @endvolt
</x-layouts.app>