<?php

use function Livewire\Volt\{state, mount, on, dehydrate};
use App\Models\{
    Course,
    CourseSection,
    Quiz,
    Question,
    StudentQuiz
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
    'currentPage'
]);

mount(function (Course $course,CourseSection $section, Quiz $quiz){

    $a = request()->query('page') ?? 1;

    $this->alpha = ['A', 'B', 'C', 'D', 'E'];
    $this->course = $course;
    $this->section = $section;
    $this->quiz = $quiz;
    $this->studentQuiz = StudentQuiz::where('student_id', auth()->user()->id)
    ->where('quiz_id', $quiz->id)
    ->first();

    $this->navigationNumber = [];
    $loc = 0;

    $firstQuestions = [];

    foreach (json_decode($this->studentQuiz->layout) as $key => $value) {

        if($value == 0){
            $loc++;
            continue;
        }

        $this->navigationNumber[] = [ 'num' => $value, 'loc' => $loc ];
    
    }

    $this->currentPage = $this->studentQuiz->current_page ?? 1;

    $this->jump_to($this->currentPage-1);

});

$jump_to = function ($idx) {
    $questionIds =  array_reduce($this->navigationNumber, function($prev,$curr) use ($idx) {
        if($curr['loc'] == $idx){
            return [...$prev, $curr['num']];
        } else {
            return $prev;
        }
    }, []);

    $this->questions = Question::whereIn('id', $questionIds)->get();
    $this->currentPage = $idx + 1;
    $this->dispatch('init-tinymce');
};


?>

<x-layouts.app>
    @volt
    <div
        class="h-full overflow-y-auto relative"
    >

        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="{{ $quiz->name }}"
            :course="$course"
            :section="$section"
        />

        <div class="p-8 flex gap-4 relative">
            <div class="bg-white p-3 h-fit order-2 w-fit rounded-lg py-4 sticky right-8 top-8">
                <p class="mb-3 font-medium" >Navigasi Soal</p>
                <div class="grid grid-cols-5 gap-3" >
                    @foreach ($navigationNumber as $q => $navNum)
                    <button wire:click="jump_to('{{ $navNum['loc'] }}')" class="chip px-3 py-1 rounded-lg border-[1.5px]  bg-gray-400/5 {{ ($currentPage ?? 1) == ($navNum['loc']+1) ? 'border-primary' : '' }} " type="button" >
                        {{ $q+1 }}
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="flex-1 order-1">
                @foreach ($questions as $i => $question)
                <div class="flex mb-4 gap-4">
                    <div class="bg-white h-fit border-[1.5px] p-4 text-sm w-[140px] rounded-lg">
                        <p class="font-semibold mb-2" >Soal {{ $i + ($quiz->question_show_number * $currentPage - 1) }}</p>
                        <p >Point {{ str_replace('.',',',$question->point) }} dari 20,00</p>
                    </div>
                    <div class="bg-white flex-1 p-6 rounded-lg">
                        <div class="font-medium" >
                            {!! $question->question !!}
                        </div>
                        <div class="mt-4 space-y-3" >
                            @if ($question->type == 'multiple-choice')
                                @foreach ($question->answers as $a => $answer)
                                    <label class="flex cursor-pointer items-center relative " >
                                        <input class="peer absolute invisible" name="question_{{ $question->id }}" type="radio">
                                        <span class="chip px-2 py-[2px] border-[1.5px] mr-3 text-sm font-medium peer-checked:border-primary peer-checked:attend transition-all" >{{ $alpha[$a] }}</span>
                                        <p>{{ $answer->answer }}</p>
                                    </label>
                                @endforeach
                            @elseif($question->type == 'option')   
                                @foreach ($question->answers as $a => $answer)
                                    <label class="flex cursor-pointer items-center" >
                                        <input value="1" name="question_{{ $question->id }}_answer" id="" type="radio" class="radio mr-3">
                                        <p>{{ $answer->answer }}</p>
                                    </label>
                                @endforeach
                            @elseif($question->type == 'essay')   
                            <label for="description" class="block mt-6" >
                                <span class="block label text-gray-600 text-[12px] mb-1" >Masukkan Jawaban</span>
                                <div wire:ignore >
                                    <textarea  ></textarea>
                                </div>
                            </label>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button type="submit" >
                        Submit
                    </x-button>
                    <x-button @click="$store.alert.cancel = true" variant="outlined" >
                        Batal
                    </x-button>
                </div>
            </div>
        </div>

    </div>

    @script
    <script>

        Livewire.on('init-tinymce', () => {
            console.log('sd')
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
                        editor.on('change', e => {
        
                        })
                    }
                });
            }, 10);
        })

    </script>
    @endscript

    @endvolt
</x-layouts.app>