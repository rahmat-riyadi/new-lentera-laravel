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
        Log::info('sd');
        $this->dispatch('notify-delay', 'Success', session('success'));
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
                    <button type="button" @click="tab = 'participants'" class="btn-tabs rounded-lg" :class="{ 'active' : tab == 'participants' }" >
                        Peserta
                    </button>
                    <button type="button" @click="tab = 'question'" class="btn-tabs rounded-lg" :class="{ 'active' : tab == 'question' }" >
                        Soal
                    </button>
                </div>

                <div
                    x-show="tab == 'participants'"
                >
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
                                    
                                </td>
                                <td>
                                    
                                </td>
                                <td class="text-center" >
                                </td>
                                <td >
                                    <a 
                                        class="btn btn-outlined" 
                                        href=""
                                    >
                                        Nilai
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div 
                    x-show="tab == 'question'" 
                >
                    <div class="flex items-center">
                        <p class="font-semibold" >Daftar Soal</p>
                        <a href="/" class="btn btn-outlined ml-auto">
                            Ubah Soal
                        </a>
                    </div>
                    <div class="flex flex-col mt-4" >
                        @foreach ($quiz->questions ?? [] as $i => $item)
                        <div class="flex border hover:bg-grey-100 items-center border-grey-300 px-3 py-4 rounded-xl mt-5" >
                            <span class="chip empty h-[26px] w-[26px] flex justify-center items-center p-0 mr-2 my-0" >{{ $i+1 }}</span>
                            <div class="text-sm" >
                                {!! $item->question !!}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

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
                            <td class="text-[#121212] text-sm" > <span class="mr-1 font-semibold text-grey-500" >:</span> {{ $quiz->answer_attempt }} Kali</td>
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
                <div class="h-4" ></div>
                <a wire:navigate.hover href="/student/quiz/{{ $quiz->id }}/answer" class="btn-outlined md:w-fit block w-full text-center">Mulai Kuis</a>
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