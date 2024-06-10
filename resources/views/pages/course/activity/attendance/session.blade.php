<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    Attendance,
    User,
    StudentAttendance,
    Context,
};

state(['course', 'section', 'attendance', 'students', 'role', 'student_status', 'status', 'note']);

mount(function (Course $course,CourseSection $section, Attendance $attendance){

});

?>

<x-layouts.app>
    @volt
    <div x-data class="h-full overflow-y-auto relative">
        <div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button @click="$store.alert.cancel = true" path="javascript:;" />
            <p class="text-sm text-[#656A7B] font-[400] flex flex-wrap leading-7 items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Ubah Kehadiran - {{ $section->name }}</span></p>
            <div class="flex items-center justify-between" >
                <h1 class="text-[#121212] text-xl font-semibold" >Ubah Sesi - {{ $section->name }}</h1>
            </div>
        </div>
    </div>
    @endvolt
</x-layouts.app>