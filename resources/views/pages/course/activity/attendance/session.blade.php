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

state(['course', 'section', 'attendance', 'sessions']);

mount(function (Course $course,CourseSection $section, Attendance $attendance){
    $this->sessions = DB::connection('moodle_mysql')
    ->table('mdl_attendance_sessions')
    ->where('attendanceid', $attendance->id)
    ->get();

    $this->sessions = $this->sessions->map(function($e){

        $date = \Carbon\Carbon::parse($e->sessdate);

        $e->date = $date->setTimezone('Asia/Makassar')->translatedFormat('l, d F Y');
        $e->start_time = $date->setTimezone('Asia/Makassar')->translatedFormat('H:i');
        if($e->duration > 0){
            $e->end_time = \Carbon\Carbon::parse($e->sessdate + $e->duration)->setTimezone('Asia/Makassar')->translatedFormat('H:i');
        }

        return $e;
    });

});

?>

<x-layouts.app>
    @volt
    <div x-data class="h-full overflow-y-auto relative">
        <div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button path="/course/{{ $course->shortname }}/activity/update/attendance/instance/{{ $attendance->id }}/section/{{ $section->section }}" />
            <p class="text-sm text-[#656A7B] font-[400] flex flex-wrap leading-7 items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Ubah Kehadiran - {{ $section->name }}</span></p>
            <div class="flex items-center justify-between" >
                <h1 class="text-[#121212] text-xl font-semibold" >Ubah Sesi - {{ $section->name }}</h1>
                <a href="/teacher/attendance/{{ $attendance->id }}/session/add" class="btn-primary flex items-center">
                    <svg class="w-[16.5px] fill-white mr-2 " width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.5 7.5H10.5V1.5C10.5 1.10218 10.342 0.720645 10.0607 0.43934C9.77936 0.158036 9.39782 0 9 0C8.60218 0 8.22064 0.158036 7.93934 0.43934C7.65804 0.720645 7.5 1.10218 7.5 1.5V7.5H1.5C1.10218 7.5 0.720645 7.65804 0.43934 7.93934C0.158036 8.22064 0 8.60218 0 9C0 9.39782 0.158036 9.77936 0.43934 10.0607C0.720645 10.342 1.10218 10.5 1.5 10.5H7.5V16.5C7.5 16.8978 7.65804 17.2794 7.93934 17.5607C8.22064 17.842 8.60218 18 9 18C9.39782 18 9.77936 17.842 10.0607 17.5607C10.342 17.2794 10.5 16.8978 10.5 16.5V10.5H16.5C16.8978 10.5 17.2794 10.342 17.5607 10.0607C17.842 9.77936 18 9.39782 18 9C18 8.60218 17.842 8.22064 17.5607 7.93934C17.2794 7.65804 16.8978 7.5 16.5 7.5Z"/>
                    </svg>
                    <span>Tambah Sesi</span>
                </a>
            </div>
        </div>

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <table class="w-full" >
                    <thead class="table-head" >
                        <tr>
                            <td class="w-[210px]" >Tanggal</td>
                            <td class="" >Waktu</td>
                            <td class="" >Jenis Kehadiran</td>
                            <td class="text-right pr-5" >Aksi</td>
                        </tr>
                    </thead>
                    <tbody class="table-body" >
                        @foreach ($sessions as $session)
                        <tr class="" >
                            <td class="py-16" >{{ $session->date }}</td>
                            <td class="py-16" >{{ $session->start_time }} {{ isset($session->end_time) ? '- '. $session->end_time : '' }}</td>
                            <td class="py-16" >
                                @if ($session->studentscanmark)
                                    Diisi oleh dosen & Mahasiswa
                                @else
                                    Diisi oleh dosen 
                                @endif
                            </td>
                            <td class="py-16 pr-5" >
                                <div class="flex justify-end" >
                                    <a class="btn-light h" href="/teacher/attendance/{{ $attendance->id }}/session/{{ $session->id }}/detail">
                                        Absen
                                    </a>
                                    <a class="btn-light bg-secodary-light text-secodary mx-4" href="/teacher/attendance/{{ $attendance->id }}/session/{{ $session->id }}/edit">
                                        Edit
                                    </a>
                                    <a class="btn-light bg-error-light text-error" href="">
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @endvolt
</x-layouts.app>