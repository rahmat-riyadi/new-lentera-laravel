<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    Attendance,
    User,
    StudentAttendance
};

state(['course', 'section', 'attendance', 'students']);

mount(function (Course $course,CourseSection $section, Attendance $attendance){
    $this->course = $course;
    $this->section = $section;
    $this->attendance = $attendance;
    $studentAttendances = StudentAttendance::where('attendance_id', $attendance->id)->get();
    $studentAttendancesIds = $studentAttendances->pluck('student_id')->toArray();
    $students = User::whereIn('id', $studentAttendancesIds)->get();
    $this->students = $students->map(function ($e) use ($studentAttendances) {
        $att = $studentAttendances->firstWhere('studentId', $e->id);
        return [
            'id' => $e->id,
            'name' => $e->firstname . ' ' . $e->lastname,
            'nim' => $e->username,
            'status' => $att->status ?? null,
            'notes' => $att->notes ?? null,
        ];
    });
});

?>

<x-layouts.app>
    @volt
    <div>
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Detail Kehadiran"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-1" >{{ $attendance->name }}</h3>
                <p class="text-grey-700 text-sm" >{{{ $attendance->intro }}}</p>
                <table class="w-full font-medium mb-5" >
                    <tr>
                        <td style="width: 210px; height: 50px;" class="text-grey-500 text-sm" >Tenggat Waktu</td>
                        <td class="text-[#121212] text-sm" >: {{ $attendance->date }}</td>
                    </tr>
                    <tr>
                        <td style="width: 210px;" class="text-grey-500 text-sm" >Kehadiran dilakukan oleh</td>
                        <td class="text-[#121212] text-sm" >: {{ $attendance->filled_by }}</td>
                    </tr>
                </table>
                <a wire:navigate.hover href="/teacher/attendance/form/{{ $attendance->id }}" class="btn-medium btn-outlined" >
                    Lakukan Kehadiran
                </a>
            </div>

            <div class="bg-white p-5 mt-6 rounded-xl">
                <table class=" w-full" >
                    <thead class="table-head" >
                        <tr>
                            <td class="" >No.</td>
                            <td class="" >Mahasiswa</td>
                            <td class="text-center" >Keterangan</td>
                            <td class="w-[170px]" >Catatan</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $i => $student)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <div class="flex items-center" >
                                    <img src="{{ ('assets/images/avatar.jpg') }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                    <div>
                                        <p class="mb-1">{{ $student['name'] }}</p>
                                        <span class="text-grey-500 " >{{ $student['nim'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center" >
                                @if($student['status'] === null)
                                    <span class="chip empty px-3" >.</span>
                                @else
                                    
                                @endif
                            </td>
                            <td>
                                -    
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