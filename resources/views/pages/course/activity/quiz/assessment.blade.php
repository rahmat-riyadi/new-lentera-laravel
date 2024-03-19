<?php

use function Livewire\Volt\{state, mount, on};
use App\Models\{
    Course,
    CourseModule,
    StudentQuiz,
    StudentQuizAnswer,
    User,
    Quiz,
    CourseSection,
    Module,
    Question,
};

state([
    'studentQuiz',
    'quiz',
    'course',
    'courseModule',
    'section',
    'student',
    'totalGrade',
    'questions',
    'alpha',
    'grades'
]);

mount(function(StudentQuiz $studentQuiz, Quiz $quiz){
    $this->grades = [];
    $this->alpha = ['A', 'B', 'C', 'D', 'E'];
    $this->studentQuiz = $studentQuiz;
    $this->totalGrade = StudentQuizAnswer::where('student_quiz_id', $studentQuiz->id)->sum('grade');
    $this->quiz = $quiz;
    $this->course = Course::find($quiz->course_id);
    $this->student = User::where('id', $this->studentQuiz->student_id)
    ->select(
        'id',
        DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
        'mdl_user.username as nim',
    )
    ->first();
    $mod = Module::where('name', 'quiz')->first();
    $this->courseModule = CourseModule::where('instance', $quiz->id)
    ->where('module', $mod->id)
    ->where('course', $this->course->id)
    ->orderBy('added', 'DESC')
    ->first();
    $this->section = CourseSection::find($this->courseModule->section);

    $questionIds = json_decode($this->studentQuiz->layout);

    $this->questions = Question::with('answers')->whereIn('questions.id', $questionIds)
    ->leftJoin('student_quiz_answers as sqa', function($q){
        $q->on('sqa.question_id', 'questions.id')
        ->where('sqa.student_quiz_id', '=', $this->studentQuiz->id);
    })
    ->leftJoin('answers as correct_answer', function($q){
        $q->on('correct_answer.question_id', 'questions.id')
        ->where('correct_answer.is_true',1);
    })
    ->distinct('questions.id')
    ->select(
        'questions.id',
        'questions.question',
        'questions.point',
        'questions.type',
        'sqa.id as student_answer_id',
        'sqa.answer_id as student_answer',
        'sqa.grade',
        'sqa.text_answer as student_text_answer',
        'correct_answer.answer as correct_answer',
        'correct_answer.id as correct_answer_id',
    )
    ->get();

    foreach($this->questions as $q){
        Log::info($q);
        if($q->type != 'essay'){
            continue;
        };

        $this->grades[$q->student_answer_id] = $q->grade;
    }

    Log::info($this->grades);

});

$submit = function (){

    DB::beginTransaction();

    try {
        foreach ($this->grades as $id => $grade) {
            StudentQuizAnswer::where('id',$id)->update([
                'grade' => $grade
            ]);
        }

        DB::commit();

        session()->flash('success', 'Penilaian berhasil disimpan');
        $this->redirect("/course/{$this->course->shortname}/activity/quiz/detail/{$this->courseModule->id}", navigate: true);
        
    } catch (\Throwable $th) {
        DB::rollBack();
        session()->flash('error', 'Terjadi kesalahan');
        $this->redirect("/course/{$this->course->shortname}/activity/quiz/detail/{$this->courseModule->id}", navigate: true);
    }

    
}

?>

<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto relative" >
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="{{ $quiz->name }}"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl" >
                <div class="flex items-center mb-5"  >
                    <img src="/images/avatar.jpg" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                    <div class="font-semibold" >
                        <p class="mb-1">{{ $student->fullname }}</p>
                        <span class="text-grey-500 " >{{ $student->nim }}</span>
                    </div>
                </div>
                <table class="w-full font-medium" >
                    <tr>
                        <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tanggal Pengerjaan</td>
                        <td class="text-[#121212] text-sm" > <span class="mr-1 text-grey-500 font-semibold" >:</span> <span>{{ \Carbon\Carbon::parse($studentQuiz->start_time)->translatedFormat('d F Y, H:i') }}</span> </td>
                    </tr>
                    <tr>
                        @php
                            $start_time = \Carbon\Carbon::parse($studentQuiz->start_time);
                            $end_time = \Carbon\Carbon::parse($studentQuiz->end_time);
                            $diff = $end_time->diffInSeconds($start_time);
                            $formatted_diff = \Carbon\Carbon::now()->diff(\Carbon\Carbon::now()->addSeconds($diff))->format('%i menit , %s detik');
                        @endphp
                        <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Waktu Pengerjaan</td>
                        <td class="text-[#121212] text-sm" > <span class="mr-1 text-grey-500 font-semibold" >:</span> {{ $formatted_diff }}</td>
                    </tr>
                    <tr>
                        <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Status</td>
                        <td class="text-[#121212] text-sm" >
                            <span class="mr-1 text-grey-500 font-semibold" >:</span>
                            @switch($studentQuiz->status)
                                @case('Selesai')
                                    <span class="chip px-3 py-[3px] attend" >{{ $studentQuiz->status }}</span>
                                    @break
                                @case('Sedang Mengerjakan')
                                    <span class="chip px-3 py-[3px]  late" >{{ $studentQuiz->status }}</span>
                                    @break
                                @default
                                <span class="chip px-3 py-[3px]  empty" >-</span>
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Nilai</td>
                        <td class="text-[#121212] text-sm" > <span class="mr-1 text-grey-500 font-semibold" >:</span> <span> <span class="text-primary" >{{ $totalGrade }}</span> dari {{ $quiz->pass_grade }} </span> </td>
                    </tr>
                </table>
            </div>

            <div class="mt-5">
                @foreach ($questions ?? [] as $i => $question)
                <div  class="flex mb-4 gap-4">
                    <div class="bg-white h-fit border-[1.5px] p-4 text-sm w-[140px] rounded-lg">
                        <p class="font-semibold mb-2" >Soal {{ $i+1 }}</p>
                        <p >Point {{ str_replace('.',',',$question->point) }} dari 20,00</p>
                    </div>
                    <div class="flex-1 space-y-3" >
                        <div class="bg-white p-6 rounded-lg">
                            <div class="font-medium" >
                                {!! $question->question !!}
                            </div>
                            <div class="mt-4 space-y-3" >
                                @if ($question->type == 'multiple-choice')
                                    @foreach ($question->answers as $a => $answer)
                                        <label class="flex cursor-pointer items-center relative " >
                                            <span class="chip px-2 py-[2px] border-[1.5px] mr-3 text-sm font-medium transition-all {{ $question->student_answer == $answer->id ? ($answer->is_true == 1) ? "border-primary attend" : 'border-error absen' : '' }}" >{{ $alpha[$a] }} </span>
                                            <p>{{ $answer->answer }}</p>
                                        </label>
                                    @endforeach
                                @elseif($question->type == 'option')   
                                    @foreach ($question->answers as $a => $answer)
                                        <label class="flex cursor-pointer items-center" >
                                            <input disabled @checked($answer->id == $question->student_answer) name="question_{{ $question->id }}"  id="" type="radio" class="radio mr-3 {{ $answer->is_true == 0 ? 'checked:ring-error checked:bg-error' : '' }}">
                                            <p>{{ $answer->answer }}</p>
                                        </label>
                                    @endforeach
                                @elseif($question->type == 'essay')   
                                <p class="text-grey-500 mb-1 font-medium" >Jawaban </p>
                                <div>
                                    {!! $question->student_text_answer !!}
                                </div>
                                @endif
                            </div>
                        </div>
                        @if ($question->type == 'essay')
                        <div class="bg-white p-6 rounded-lg border-l-4 font-medium border-l-primary " >
                            <label for="grade_{{ $question->id }}" class="" >
                                <span class="block label text-gray-600 text-[12px] mb-1" >Nilai</span>
                                <input type="text" id="grade_{{ $question->id }}" wire:model="grades.{{ $question->student_answer_id }}"  placeholder="Masukkan Nilai"  class="text-field w-[210px]">
                            </label>
                            <p class="text-grey-500 mb-1 mt-3" >Jawaban Benar </p>
                            <div>
                                {!! $question->correct_answer !!}
                            </div>
                        </div>    
                        @else
                        <div class="bg-white p-6 rounded-lg border-l-4 font-medium {{ $question->correct_answer_id == $question->student_answer ? 'border-l-primary' : 'border-l-error' }} " >
                            @if ($question->correct_answer_id == $question->student_answer)
                            <span class="text-primary font-semibold mb-2 block" >Benar</span>
                            @else
                            <span class="text-error font-semibold mb-2 block" >Salah</span>
                            @endif
                            <p>Jawaban Benar : {{ $question->correct_answer }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                <div class="flex justify-end gap-x-3" >
                    <x-button  @click="$store.alert.save = true">
                        Submit
                    </x-button>
                    <x-button @click="$store.alert.cancel = true" variant="outlined" >
                        Batal
                    </x-button>
                </div>
            </div>

        </div>

        <x-alert
            show="$store.alert.cancel"
            onCancel="$store.alert.cancel = false"
            type="warning"
            title="Batal"
            message="Batalkan penilaian kuis ?"
            cancelText="Periksa Kembali"
        />

        <x-alert
            show="$store.alert.save"
            onCancel="$store.alert.save = false"
            type="success"
            title="Pemberitahuan"
            message="Yakin ingin menyimpan penilaian ?"
            cancelText="Periksa Kembali"
            onOk="$wire.submit()"
        />

    </div>

    @script
    <script>
        Alpine.store('alert', {
            cancel: false,
            save: false,
            loading: false
        })
    </script>
    @endscript

    @endvolt
</x-layouts.app>