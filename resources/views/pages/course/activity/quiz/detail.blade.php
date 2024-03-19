<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    User,
    Context,
    Role,
    Quiz,
    StudentQuiz
};

state([
    'course', 
    'section', 
    'quiz', 
    'role', 
    'students',
    'studentQuiz',
    'finishedStudent'
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
        ->leftJoin('lentera_v2.student_quizzes as sq', function($q) use ($quiz) {
            $q->on('sq.student_id', '=', 'mdl_user.id')
            ->where('sq.quiz_id', $quiz->id);
        })
        ->leftjoin('lentera_v2.student_quiz_answers as sqa', 'sqa.student_quiz_id', 'sq.id')
        ->groupBy(
            'mdl_user.id',
            'fullname',
            'nim',
            // 'quiz_id',
            'sqa.student_quiz_id',
            'sq.status',
            'sq.start_time',
            'sq.end_time',
        )
        ->select(
            'mdl_user.id',
            DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
            'mdl_user.username as nim',
            // 'sq.id as quiz_id',
            'sqa.student_quiz_id',
            'sq.status',
            'sq.start_time',
            'sq.end_time',
            DB::raw("SUM(sqa.grade) as total"),
        )
        ->get();

        $this->finishedStudent = $this->students->filter(fn($e) => !is_null($e->student_quiz_id))->count();

        // $this->submitted_count = $this->students->filter(fn($e) => !is_null($e->created_at))->count();
        // $this->need_grading_count = $this->students->filter(fn($e) => is_null($e->grade) && !is_null($e->created_at))->count();
        
    } else {
        $this->studentQuiz = StudentQuiz::where('quiz_id', $quiz->id)
        ->where('student_id', auth()->user()->id)
        ->first();
        
    }

    if(session('success')){
        $this->dispatch('notify-delay', 'success', session('success'));
    }

});



?>

<x-layouts.app>
    @volt
    <div
        x-data="{ tab: 'question' }"
        class="h-full overflow-y-auto relative"
    >
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="{{ $quiz->name }}"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            @if ($role != 'student')
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $quiz->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $quiz->description !!}</p>
                <div class="flex mt-4">
                    <table class="w-full font-medium " >
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
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Waktu Pengerjaan</td>
                            <td class="text-[#121212] text-sm" >: {{ $start_date->diffInMinutes($end_date) }} Menit</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Percobaan Menjawab</td>
                            <td class="text-[#121212] text-sm" >: {{ $quiz->answer_attempt  }}</td>
                        </tr>
                    </table>
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Peserta</td>
                            <td class="text-[#121212] text-sm" >: {{ count($students) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Telah Mengerjakan</td>
                            <td class="text-[#121212] text-sm" >: {{ $finishedStudent }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" ></td>
                            <td class="text-[#121212] text-sm" ></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="p-5 bg-white rounded-xl mt-6">
                <table class="w-full" >
                    <thead class="table-head" >
                        <tr>
                            <td class="w-[240px]" >Mahasiswa</td>
                            <td class="w-[170px]" >Waktu Pengerjaan</td>
                            <td class="w-[160px]" >Status</td>
                            <td class="text-center w-[150px]" >Total Nilai</td>
                            <td class="w-[90px]" >Aksi</td>
                        </tr>
                    </thead>
                    <tbody class="table-body text-sm" >
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
                            <td>
                                @php
                                    $start_time = \Carbon\Carbon::parse($student->start_time);
                                    $end_time = \Carbon\Carbon::parse($student->end_time);
                                    $diff = $end_time->diffInSeconds($start_time);
                                    $formatted_diff = \Carbon\Carbon::now()->diff(\Carbon\Carbon::now()->addSeconds($diff))->format('%i menit , %s detik');
                                @endphp
                                {{ $formatted_diff }}
                            </td>
                            <td >
                                @switch($student->status)
                                    @case('Selesai')
                                        <span class="chip px-3 py-[3px] attend" >{{ $student->status }}</span>
                                        @break
                                    @case('Sedang Mengerjakan')
                                        <span class="chip px-3 py-[3px]  late" >{{ $student->status }}</span>
                                        @break
                                    @default
                                    <span class="chip px-3 py-[3px]  empty" >-</span>
                                @endswitch
                            </td>
                            <td class="text-center" >
                                {{ $student->total }}
                            </td>
                            <td >
                                <a 
                                    class="btn btn-outlined" 
                                    wire:navigate.hover
                                    href="/teacher/quiz/{{ $quiz->id }}/assessment/{{ $student->student_quiz_id }}"
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
                <h3 class="font-semibold text-lg mb-2" >{{ $quiz->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $quiz->description !!}</p>
                <div class="flex mt-4">
                    <table class="w-full font-normal md:font-medium" >
                        @php
                            $start_date = \Carbon\Carbon::parse($quiz->start_date);
                            $end_date = \Carbon\Carbon::parse($quiz->due_date);
                        @endphp
                        <tr>
                            <td style="height: 37px;" class="text-grey-500 text-sm md:w-[210px]" >Tenggat Waktu</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> 
                                {{ Carbon\Carbon::parse($quiz->end_date)->translatedFormat('d F Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="height: 37px;" class="md:w-[210px] text-grey-500 text-sm" >Waktu Mulai</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> {{ $start_date->format('H:i') }} - {{ $end_date->format('H:i') }}</td>
                        </tr>
                        <tr>
                            <td style="height: 37px;" class="md:w-[210px] text-grey-500 text-sm" >Waktu Pengerjaan</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> {{ $start_date->diffInMinutes($end_date) }} Menit</td>
                        </tr>
                    </table>
                    <table class="w-full font-medium md:block hidden" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Jumlah Percobaan</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> {{ $quiz->answer_attempt - $studentQuiz->attempt  }} Kali</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Status</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> <span class="chip py-1 empty" >-</span></td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Penilaian</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> <span class="chip py-1 empty" >-</span></td>
                        </tr>
                    </table>
                </div>
                @if ($studentQuiz->attempt >= $quiz->answer_attempt)

                @else
                <div class="h-4" ></div>
                <a href="/student/quiz/{{ $quiz->id }}/answer" class="btn-outlined md:w-fit block w-full text-center">Mulai Kuis</a>
                @endif
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