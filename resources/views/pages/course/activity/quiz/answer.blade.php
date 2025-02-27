<?php

use function Livewire\Volt\{state, mount, on, updated, updating};
use App\Models\{
    Course,
    CourseSection,
    Quiz,
    Question,
    StudentQuiz,
    StudentQuizAnswer,
    CourseModule,
    Answer,
};

state([
    'course', 
    'section', 
    'quiz', 
    'role', 
    'students',
    'questions',
    'alpha',
    'answers',
    'navigationNumber',
    'studentQuiz',
    'currentPage',
    'totalPage',
    'answeredQuestions',
    'courseModule'
]);

mount(function (Course $course, CourseSection $section, Quiz $quiz, CourseModule $courseModule){
    $this->alpha = ['A', 'B', 'C', 'D', 'E'];
    $this->courseModule = $courseModule;
    $this->course = $course;
    $this->section = $section;
    $this->quiz = $quiz;
    $this->studentQuiz = StudentQuiz::where('student_id', auth()->user()->id)
    ->where('quiz_id', $quiz->id)
    ->first();

    if(is_null($this->studentQuiz->attempt)){
        $this->studentQuiz->status = 'Sedang Bekerja';
        $this->studentQuiz->start_time = \Carbon\Carbon::now();
        $this->studentQuiz->save();
    }

    if($this->studentQuiz->attempt >= $quiz->answer_attempt){
        session()->flash('success', 'Jumlah percobaan melewati batas');
        $this->redirect("/course/{$course->shortname}/activity/quiz/detail/{$courseModule->id}", navigate: true);
        return;
    }


    $this->navigationNumber = [];
    $this->totalPage = 1;

    $this->answeredQuestions = StudentQuizAnswer::where('student_quiz_id', $this->studentQuiz->id)
    ->whereNotNull('answer_id')
    ->orWhereNotNull('text_answer')
    ->pluck('question_id')
    ->toArray();

    foreach (json_decode($this->studentQuiz->layout) as $key => $value) {

        if($value == 0){
            $this->totalPage++;
            continue;
        }

        $this->navigationNumber[$value] = [
            'question_id' => $value,
            'loc' => $this->totalPage,
            'is_done' => in_array($value, $this->answeredQuestions)
        ];
    
    }

    $this->currentPage = $this->studentQuiz->current_page ?? 1;

    $this->jump_to($this->currentPage);

});

$jump_to = function ($idx) {

    $questionIds =  array_reduce($this->navigationNumber, function($prev,$curr) use ($idx) {
        if($curr['loc'] == $idx){
            return [...$prev, $curr['question_id']];
        } else {
            return $prev;
        }
    }, []);

    $this->questions = Question::with('answers')->whereIn('questions.id', $questionIds)
    ->orderByRaw("FIELD(questions.id, " . implode(",", $questionIds) . ")")
    ->leftJoin('student_quiz_answers as qa', function($q){
        $q->on('qa.question_id', '=', 'questions.id')
        ->where('qa.student_quiz_id', $this->studentQuiz->id);
    })
    ->select(
        'questions.id',
        'questions.question',
        'questions.type',
        'questions.point',
        'qa.answer_id as student_answer',
        'qa.text_answer as student_answer_text'
    )
    ->get();

    foreach($this->questions as $q){
        $this->answers[$q->id] = $q->student_answer;
    }
    

    $this->currentPage = $idx;
    $this->dispatch('init-tinymce', $this->questions);
};

$submit = function (){

    $this->answers = [];

    if($this->totalPage != $this->currentPage){
        $this->currentPage++;
        $this->jump_to($this->currentPage);
        return;
    }

    $this->studentQuiz->end_time = \Carbon\Carbon::now();
    $this->studentQuiz->attempt = $this->studentQuiz->attempt + 1;
    $this->studentQuiz->status = 'Selesai Mengerjakan';
    $this->studentQuiz->save();

    $this->dispatch('finish-quiz');

    
};

$handle_change_answer = function ($question_id, $answer_id){

    DB::beginTransaction();

    $answer = Answer::find($answer_id);

    try {
        $instance = StudentQuizAnswer::updateOrCreate(
            [
                'student_quiz_id' => $this->studentQuiz->id,
                'question_id' => $question_id,
            ],
            [
                'student_quiz_id' => $this->studentQuiz->id,
                'question_id' => $question_id,
                'answer_id' => $answer_id,
                'grade' => $answer->is_true == 1 ? $answer->question->point : 0,
            ]
        );
        $this->answeredQuestions[] = $instance->id;
        $this->navigationNumber[$question_id]['is_done'] = true;
        DB::commit();
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
        DB::rollBack();
    }

};

$handle_submit_essay = function ($question_id ,$val){
    DB::beginTransaction();

    try {
        $instance = StudentQuizAnswer::updateOrCreate(
            [
                'student_quiz_id' => $this->studentQuiz->id,
                'question_id' => $question_id,
            ],
            [
                'student_quiz_id' => $this->studentQuiz->id,
                'question_id' => $question_id,
                'text_answer' => $val,
            ]
        );
        $this->answeredQuestions[] = $instance->id;
        $this->navigationNumber[$question_id]['is_done'] = true;
        DB::commit();
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
        DB::rollBack();
    }

};

on(['submit-essay' => 'handle_submit_essay']);

?>

<x-layouts.app>
    @volt
    <div
        class="h-screen md:h-full overflow-y-auto relative"
    >

        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="{{ $quiz->name }}"
            :course="$course"
            :section="$section"
        />

        <div class="p-8 flex flex-col md:flex-row gap-4 relative">
            <div class="bg-white p-4 rounded-xl md:hidden block sticky top-4 shadow" >
                <p class="font-semibold mr-auto" >Navigasi Soal</p>
                <div class="flex mt-4 items-center" >
                    <button>
                        <img src="{{ asset('assets/icons/arrow-right-flat.svg') }}" alt="">
                    </button>
                    <div class="grow flex gap-x-4 overflow-x-scroll snap-mandatory no-scrollbar mx-2" >
                        @foreach ($navigationNumber as $q => $navNum)
                        <button wire:click="jump_to('{{ $navNum['loc'] }}')" class="chip h-[40px] w-[40px] aspect-square rounded-lg border-[1.5px]  bg-gray-400/5 {{ ($currentPage) == ($navNum['loc']) ? 'border-secodary' : '' }} {{ $navNum['is_done'] ? 'attend border-primary': '' }} snap-start" type="button" >
                            {{ $loop->index + 1 }}
                        </button>
                        @endforeach
                    </div>
                    <button class="rotate-180" >
                        <img src="{{ asset('assets/icons/arrow-right-flat.svg') }}" alt="">
                    </button>
                </div>
            </div>
            <div class="bg-white p-3 h-fit order-2 w-fit hidden md:block rounded-lg py-4 sticky right-8 top-8">
                <p class="mb-3 font-medium" >Navigasi Soal</p>
                <div class="grid grid-cols-5 gap-3" >
                    @foreach ($navigationNumber as $q => $navNum)
                    <button wire:click="jump_to('{{ $navNum['loc'] }}')" class="chip px-3 py-1 rounded-lg border-[1.5px]  bg-gray-400/5 {{ ($currentPage) == ($navNum['loc']) ? 'border-secodary' : '' }} {{ $navNum['is_done'] ? 'attend border-primary': '' }} " type="button" >
                        {{ $loop->index + 1 }}
                    </button>
                    @endforeach
                </div>
            </div>
            <div wire:loading wire:target="jump_to, submit" class="flex-1 order-1">
                @for ($i = 0; $i < 3; $i++)
                <div  class="flex mb-4 gap-4">
                    <div class="bg-white border-[1.5px] p-4 text-sm w-[140px] rounded-lg h-[80px]">
                        <div class="w-[30px] h-[8px] animate-pulse bg-gray-200 my-2 rounded-lg" ></div>
                        <div class="w-[80px] h-[8px] animate-pulse bg-gray-200 my-2 rounded-lg" ></div>
                    </div>
                    <div class="bg-white flex-1 p-6 rounded-lg h-[210px]">
                        <div class="w-full h-[10px] animate-pulse bg-gray-200 rounded-lg"  ></div>
                        <div class="w-[80%] h-[10px] animate-pulse bg-gray-200 my-2 rounded-lg" ></div>
                        <div class="w-full h-[10px] animate-pulse bg-gray-200 rounded-lg"  ></div>
                        <div class="w-[50%] h-[10px] animate-pulse bg-gray-200 my-2 rounded-lg" ></div>
                    </div>
                </div>
                @endfor
            </div>
            <div wire:loading.remove wire:target="submit, jump_to" class="flex-1 order-1">
                @foreach ($questions as $i => $question)
                <div  class="flex flex-col md:flex-row mb-4 gap-4">
                    <div class="bg-white h-full border-[1.5px] p-4 text-sm md:w-[140px] rounded-lg">
                        <p class="font-semibold mb-2" >Soal {{ $i + ($quiz->question_show_number * $currentPage - 1) - 1 }}</p>
                        <p >Point {{ str_replace('.',',',$question->point) }} dari 100,00</p>
                    </div>
                    <div class="bg-white flex-1 p-6 rounded-lg">
                        <div class="font-medium" >
                            {!! $question->question !!}
                        </div>
                        <div class="mt-4 space-y-3" >
                            @if ($question->type == 'multiple-choice')
                                @foreach ($question->answers as $a => $answer)
                                    <label class="flex cursor-pointer items-center relative " >
                                        <input wire:change="handle_change_answer({{ $question->id }}, {{ $answer->id }})" wire:model="answers.{{ $question->id }}" value="{{ $answer->id }}" class="peer absolute invisible" name="question_{{ $question->id }}" type="radio">
                                        <span class="chip px-2 py-[2px] border-[1.5px] mr-3 text-sm font-medium peer-checked:border-primary peer-checked:attend transition-all" >{{ $alpha[$a] }}</span>
                                        <p>{{ $answer->answer }}</p>
                                    </label>
                                @endforeach
                            @elseif($question->type == 'option')   
                                @foreach ($question->answers as $a => $answer)
                                    <label class="flex cursor-pointer items-center" >
                                        <input wire:change="handle_change_answer({{ $question->id }}, {{ $answer->id }})" name="question_{{ $question->id }}" wire:model="answers.{{ $question->id }}" value="{{ $answer->id }}" id="" type="radio" class="radio mr-3">
                                        <p>{{ $answer->answer }}</p>
                                    </label>
                                @endforeach
                            @elseif($question->type == 'essay')   
                            <label for="description" class="block mt-6" >
                                <span class="block label text-gray-600 text-[12px] mb-1" >Masukkan Jawaban</span>
                                <input type="hidden" class="question_{{ $question->id }}" value="{{ $question->student_answer_text }}" >
                                <div wire:ignore class="question_{{ $question->id }}"  >
                                    <textarea class="question_{{ $question->id }}" ></textarea>
                                </div>
                            </label>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button type="button" wire:click="submit" >
                        {{ $totalPage != $currentPage ? 'Selanjutnya' : 'Selesai' }}
                    </x-button>
                    <x-button @click="$store.alert.cancel = true" variant="outlined" >
                        Batal
                    </x-button>
                </div>
            </div>
        </div>

        <x-alert
            show="$store.alert.save"
            onCancel="$store.alert.save = false"
            type="success"
            title="Pemberitahuan"
            message="Yakin ingin menyelesaikan Kuis ?"
            cancelText="Periksa Kembali"
            okText="Selesai"
            onOk="Livewire.navigate('/course/{{ $course->shortname }}/activity/quiz/detail/{{ $courseModule->id }}')"
        />

    </div>

    @script
    <script>

        Alpine.store('alert', {
            cancel: false,
            save: false,
            loading: false,
            save: false
        })

        Livewire.on('finish-quiz', () => {
            Alpine.store('alert').save = true
        })

        Livewire.on('init-tinymce', ([ questions ]) => {

            setTimeout(() => {
                tinymce.init({
                    height: 280,
                    menubar: false,
                    selector: 'textarea',
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                    file_picker_types: 'file image media',
                    images_upload_url: '/api/question/image',
                    setup: editor => {

                        editor.on('init', () => {
                            const idx = tinymce.activeEditor.targetElm.classList[0].split('_')[1]
                        })

                        editor.on('change', e => {
                            const idx = tinymce.activeEditor.targetElm.parentElement.previousElementSibling.classList[0].split('_')[1]
                            console.log(idx)
                            $wire.$dispatch('submit-essay', { question_id: idx, val: tinymce.activeEditor.getContent() })
                        })
                    }
                });
                questions.forEach(e => {
                    console.log(e)
                    if(e.type == 'essay') {
                        // console.log(e)
                        const el = document.querySelector('input.question_'+e.id)
                        el.nextElementSibling.querySelector('textarea').innerHTML = e.student_answer_text
                        el.nextElementSibling.setAttribute('wire:ignore')
                    }
                })
            }, 0);

        })

    </script>
    @endscript

    @endvolt
</x-layouts.app>