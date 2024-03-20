<?php

use function Livewire\Volt\{state, mount, on};
use App\Models\Enrol;
use App\Models\Course;
use Carbon\Carbon;
state(['curr_tab', 'courses', 'showed_courses']);

mount(function () {
    $this->curr_tab = 'current';
    $this->get_courses();
    $this->change_courses('current');
});

$change_tab = function ($val){
    $this->curr_tab = $val;
    $this->change_courses($val);
};

$change_courses = function ($mode){
    $this->showed_courses = $this->courses->filter(function($e) use ($mode) {
        if($mode == 'past'){
            return $e->enddate < time() && $e->enddate != 0;
        }

        if($mode == 'current'){
            return !($e->startdate > time()) && !($e->enddate < time() && $e->enddate != 0);
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
    $enrolIds = auth()->user()->enrolments->pluck('enrolid');
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
    Log::info(json_decode($this->courses));
};

?>

<x-layouts.app>
    @volt
    <div class="flex flex-col md:flex-row h-full p-7 pb-0 gap-x-7 no-scrollbar grow overflow-y-auto" >
        <div class="order-2 md:order-1" >
            {{-- <div class="bg-white p-4 rounded-xl" >
                s
            </div> --}}
            <div class="bg-white p-4 rounded-xl" >
                <p class="font-semibold text-lg text-[#121212] mb-4" >Mata Kuliah Baru diakses</p>
                <div class="grid gap-5 sx:grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3" >
                    <x-course-card
                        studyProgram="Teknik Informatika"
                        course="kursus "
                    />
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
                <div class="grid gap-4 sx:grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3" >
                    @foreach ($showed_courses as $course)
                    <x-course-card
                        studyProgram="{{ $course->categoryInfo->name }}"
                        course="{{ $course->fullname }}"
                    />
                    @endforeach
                </div>
            </div>
            <div class="h-10" ></div>
        </div>
        <div class="md:w-[290px] order-1 md:order-2" >
            <div class="flex justify-between items-center mb-3">
                <p class="body-1 text-lg md:text-base font-semibold" >Pengumuman</p>
                <button class="text-sm text-primary-dark" >Lihat Semua</button>
            </div>
            <div class="bg-primary-light p-3 rounded-xl" >
                <p class="text-xs mb-1" >12 Maret 2023, 10.24</p>
                <p class="font-semibold leading-5 md:text-sm mb-3 md:mb-2" >Semua dosen harap segera memasukkan materi pada mata kuliah yang diajarkan</p>
                <p class="text-sm font-medium md:font-normal md:text-xs" >Dosen diharap memasukkan materi disetiap pertemuan sebelum perkuliahan berlangsung. Agar mahasiswa dapat melihat dan mempelajari terlebih dahulu.</p>
            </div>
            <p class="body-1 font-semibold mt-4 mb-3" >Aktivitas Akan Datang</p>
            <div class="bg-white py-10 rounded-xl mb-10" >
                <p class="text-center text-sm text-grey-500 font-normal" >Belum ada aktivitas</p>
            </div>
        </div>
    </div>
    @endvolt
</x-layouts.app>