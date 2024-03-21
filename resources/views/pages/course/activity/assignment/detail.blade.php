<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    Assignment,
    AssignmentSubmission,
    User,
    Context,
    Role,
};

state([
    'course', 
    'section', 
    'assignment', 
    'role', 
    'students',
    'submitted_count',
    'need_grading_count',
    'student_submission'
]);

mount(function (Course $course,CourseSection $section, Assignment $assignment){
    $ctx = Context::where('contextlevel', 50)->where('instanceid', 4)->first();
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
    $this->section = $section;

    if($this->role != 'student'){

        $role = Role::where('shortname', 'student')->first();

        $studentIds = DB::connection('moodle_mysql')->table('mdl_enrol')
                ->where('mdl_enrol.courseid', $course->id)
                ->where('mdl_enrol.roleid', $role->id)
                ->where('mdl_user_enrolments.userid', '!=',auth()->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                ->pluck('mdl_user_enrolments.userid');

        $this->students = User::query()
        ->whereIn('mdl_user.id', $studentIds)
        ->leftJoin('lentera_v2.assignment_submissions as s', function($q) use ($assignment) {
            $q->on('s.student_id', '=', 'mdl_user.id')
            ->where('s.assignment_id', $assignment->id);
        })
        ->select(
            'mdl_user.id',
            's.id as assignment_submission_id',
            DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
            'mdl_user.username as nim',
            's.grade',
            's.grading_time',
            's.created_at'
        )
        ->get();

        $this->submitted_count = $this->students->filter(fn($e) => !is_null($e->created_at))->count();
        $this->need_grading_count = $this->students->filter(fn($e) => is_null($e->grade) && !is_null($e->created_at))->count();
        
    } else {
        $this->student_submission = AssignmentSubmission::where('student_id', auth()->user()->id)
        ->where('assignment_id', $assignment->id)
        ->first();
    }

    if(session('success')){
        $this->dispatch('notify-delay', 'Success', session('success'));
    }

});



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
            @if ($role != 'student')
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $assignment->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $assignment->description !!}</p>
                <div class="flex mt-4">
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tenggat Waktu</td>
                            <td class="text-[#121212] text-sm" >: {{ Carbon\Carbon::parse($assignment->due_date)->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Waktu Tersisa</td>
                            <td class="text-[#121212] text-sm" >: {{ Carbon\Carbon::parse($assignment->due_date)->diffForHumans(['parts' => 2]) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Jenis Pengiriman</td>
                            <td class="text-[#121212] text-sm" >: {{ $assignment->configs()->where('name', 'type')->first()->value == 'onlinetext' ? 'Text Daring' : 'File'  }}</td>
                        </tr>
                    </table>
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Peserta</td>
                            <td class="text-[#121212] text-sm" >: {{ count($students) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Terkumpul</td>
                            <td class="text-[#121212] text-sm" >: {{ $submitted_count }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Belum Dinilai</td>
                            <td class="text-[#121212] text-sm" >: {{ $need_grading_count }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="bg-white p-5 mt-6 rounded-xl">
                <table class=" w-full" >
                    <thead class="table-head" >
                        <tr>
                            <td class="" >Mahasiswa</td>
                            <td >Waktu Pengumpulan</td>
                            <td class="w-[170px]" >Status</td>
                            <td class="text-center  w-[150px]" >Total Nilai</td>
                            <td class="w-[150px]" >Aksi</td>
                        </tr>
                    </thead>
                    <tbody class="table-body" >
                        @foreach ($students as $student)
                        <tr>
                            <td>
                                <div class="flex items-center" >
                                    <img src="{{ asset('/assets/images/avatar.webp') }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                    <div>
                                        <p class="mb-1">{{ $student->fullname }}</p>
                                        <span class="text-grey-500 " >{{ $student->nim }}</span>
                                    </div>
                                </div>
                            </td>
                            <td >
                                @if (!empty($student->created_at))
                                {{ \Carbon\Carbon::parse($student->created_at)->translatedFormat('d F Y, H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                {{-- <p class="chip {{ is_null($student->is_late) ? 'empty' : (($student->is_late) ? 'late' : 'attend' ) }} text-center px-3 text-xs w-fit font-medium rounded-xl">{{ is_null($student->is_late) ? 'Belum' : (!$student->is_late ? 'Telah' : 'Terlambat')}} Dikumpulkan</p> --}}
                                @if (is_null($student->created_at))
                                    <p class="chip empty text-center px-3 text-xs w-fit font-medium rounded-xl">Belum Dikumpulkan</p>
                                @else
                                    @php
                                        $sub_time = \Carbon\Carbon::parse($student->created_at);
                                        $assign_time = \Carbon\Carbon::parse($assignment->due_date);
                                    @endphp
                                    @if ($sub_time->gt($assign_time))
                                    <p class="chip late text-center px-3 text-xs w-fit font-medium rounded-xl">Terlambat Dikumpulkan</p>
                                    @else
                                    <p class="chip attend text-center px-3 text-xs w-fit font-medium rounded-xl">Dikumpulkan</p>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center" >
                                {{ !is_null($student->grade) ? number_format($student->grade, 2, ',') : '0,00' }}
                            </td>
                            <td >
                                <a 
                                    class="btn btn-outlined" 
                                    href='{{ is_null($student->assignment_submission_id) ? "javascript:;" : "/teacher/assignment/$assignment->id/grade/$student->assignment_submission_id" }}'
                                >
                                    Nilai
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>    
            @else
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $assignment->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $assignment->description !!}</p>
                <table class="w-full font-normal md:font-medium mt-4" >
                    <tr>
                        <td style="height: 37px;" class="text-grey-500 text-sm  md:w-[210px]" >Batas Waktu</td>
                        <td class="text-[#121212] text-sm" > <span class="mr-1" >:</span> {{ Carbon\Carbon::parse($assignment->due_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        @php
                            $end_time = \Carbon\Carbon::parse($assignment->due_date);
                            $now = \Carbon\Carbon::now();
                            // $diff = $end_time->diffInSeconds($start_time);
                        @endphp
                        <td style="height: 37px;" class="text-grey-500 text-sm  md:w-[210px]" >Waktu Tersisa</td>
                        <td class="text-[#121212] text-sm" > <span class="mr-1" >:</span> {{ Carbon\Carbon::parse($assignment->due_date)->diff()->format('%H Jam %i Menit') }}</td>
                    </tr>
                    <tr>
                        <td style="height: 37px;" class="text-grey-500 text-sm  md:w-[210px]" >Status</td>
                        <td class="text-[#121212] text-sm" >
                            <span class="mr-1" >:</span>
                            @if (!empty($student_submission))
                            <span class="chip px-3 py-1 text-xs rounded-md attend" >Dikumpulkan</span>
                            @else
                            <span class="chip empty" >-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style=" height: 37px;" class="text-grey-500 text-sm md:w-[210px]" >Penilaian</td>
                        <td class="text-[#121212] text-sm" >
                            <span class="mr-1" >:</span>
                            @if (!empty($student_submission->grade))
                            <span class="chip px-2 py-1  rounded-md attend" > {{ $student_submission->grade }} </span>
                            @else
                            <span class="chip empty" >-</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <div class="h-4" ></div>
                <a wire:navigate.hover href="/student/assignment/{{ $assignment->id }}/submit" class="btn btn-outlined text-center inline-block w-full md:w-fit">Ajukan Penugasan</a>
            </div>    
            @endif
        </div>

        <x-toast/>

    </div>

    @script
    <script>

        Livewire.on('notify-delay', ([ type, message ]) => {

            setTimeout(() => {
                Alpine.store('toast').show = true
                Alpine.store('toast').type = type
                Alpine.store('toast').message = message
                setTimeout(() => {
                    Alpine.store('toast').show = false
                }, 2000);
            }, 100)

        })
        
    </script>
    @endscript

    @endvolt
</x-layouts.app>