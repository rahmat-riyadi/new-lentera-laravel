<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    User,
    Context,
    Role,
    Quiz
};

state([
    'course', 
    'section', 
    'quiz', 
    'role', 
    'students',
]);

mount(function (Course $course,CourseSection $section, Quiz $quiz){
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
    $this->quiz = $quiz;

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
        // ->leftJoin('lentera_v2.assignment_submissions as s', function($q) use ($assignment) {
        //     $q->on('s.student_id', '=', 'mdl_user.id')
        //     ->where('s.assignment_id', $assignment->id);
        // })
        ->select(
            'mdl_user.id',
            // 's.id as assignment_submission_id',
            DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
            'mdl_user.username as nim',
            // 's.grade',
            // 's.grading_time',
            // 's.created_at'
        )
        ->get();

        // $this->submitted_count = $this->students->filter(fn($e) => !is_null($e->created_at))->count();
        // $this->need_grading_count = $this->students->filter(fn($e) => is_null($e->grade) && !is_null($e->created_at))->count();
        
    } else {
        // $this->student_submission = AssignmentSubmission::where('student_id', auth()->user()->id)
        // ->where('assignment_id', $assignment->id)
        // ->first();
    }

    if(session('success')){
        $this->dispatch('notify-delay', 'Success', session('success'));
    }

});



?>

<x-layouts.app>
    @volt
    <div
        x-data="{ tab: 'participants' }"
        class="h-full overflow-y-auto relative"
    >
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Detail Quiz"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $quiz->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $quiz->description !!}</p>
                <div class="flex mt-4">
                    <table class="w-full font-medium" >
                        @php
                            $start_date = \Carbon\Carbon::parse($quiz->start_date);
                            $end_date = \Carbon\Carbon::parse($quiz->due_date);
                        @endphp
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tenggat Waktu</td>
                            <td class="text-[#121212] text-sm" >: 
                                @if ($start_date->diffInDays($end_date) != 0)
                                {{ Carbon\Carbon::parse($quiz->start_date)->translatedFormat('d/m/Y, H:i') }} - {{ Carbon\Carbon::parse($quiz->due_date)->translatedFormat('d/m/Y, H:i') }}
                                @else
                                {{ $start_date->translatedFormat('d F Y, H:i') }} - {{ $end_date->translatedFormat('H:i') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Waktu Tersisa</td>
                            <td class="text-[#121212] text-sm" >: {{ Carbon\Carbon::parse($quiz->due_date)->diffForHumans(['parts' => 2]) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Percobaan Menjawab</td>
                            <td class="text-[#121212] text-sm" >: {{ $quiz->answer_attempt  }}</td>
                        </tr>
                    </table>
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tentang Soal</td>
                            <td class="text-[#121212] text-sm" >: {{ $quiz->shuffle_answer == 1 ? 'Ya' : 'Tidak' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tampilkan Nilai</td>
                            <td class="text-[#121212] text-sm" >: {{ $quiz->show_grade == 1 ? 'Ya' : 'Tidak' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tampilkan Jawaban</td>
                            <td class="text-[#121212] text-sm" >: {{ $quiz->show_answers == 1 ? 'Ya' : 'Tidak' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="p-5 bg-white rounded-xl mt-6">
                <div class="flex space-x-3 mb-6">
                    <button @click="tab = 'participants'" class="btn-tabs rounded-lg" :class="{ 'active' : tab == 'participants' }" >
                        Peserta
                    </button>
                    <button @click="tab = 'question'" class="btn-tabs rounded-lg" :class="{ 'active' : tab == 'question' }" >
                        Soal
                    </button>
                </div>

                <div x-show="tab = 'participants'" >
                    <table class="w-full" >
                        <thead class="table-head" >
                            <tr>
                                <td class="" >Mahasiswa</td>
                                <td class="w-[130px]" >Status</td>
                                <td class="w-[170px]" >Lama Pengerjaan</td>
                                <td class="  w-[150px]" >Total Nilai</td>
                                <td class="w-[120px]" >Aksi</td>
                            </tr>
                        </thead>
                        <tbody class="table-body" >
                            @foreach ($students as $student)
                            <tr>
                                <td>
                                    <div class="flex items-center" >
                                        <img src="/images/avatar.jpg" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                        <div>
                                            <p class="mb-1">{{ $student->fullname }}</p>
                                            <span class="text-grey-500 " >{{ $student->nim }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td >
                                    {{-- @if (!empty($student->created_at))
                                    {{ \Carbon\Carbon::parse($student->created_at)->translatedFormat('d F Y, H:i') }}
                                    @else
                                        -
                                    @endif --}}
                                </td>
                                <td>
                                    {{-- @if (is_null($student->created_at))
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
                                    @endif --}}
                                </td>
                                <td class="text-center" >
                                    {{-- {{ !is_null($student->grade) ? number_format($student->grade, 2, ',') : '0,00' }} --}}
                                </td>
                                <td >
                                    <a 
                                        class="btn btn-outlined" 
                                        {{-- href='{{ is_null($student->assignment_submission_id) ? "javascript:;" : "/teacher/assignment/$assignment->id/grade/$student->assignment_submission_id" }}' --}}
                                    >
                                        Nilai
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div x-show="tab = 'question'" >
                    asdfas
                </div>

            </div>

        </div>

    </div>
    @endvolt
</x-layouts.app>