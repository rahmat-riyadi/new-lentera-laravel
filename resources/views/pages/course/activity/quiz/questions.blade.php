<?php

use function Livewire\Volt\{state, mount, on, form};
use App\Livewire\Forms\Quiz\QuestionForm;
use App\Models\{
    Course,
    CourseSection,
    Quiz,
};

state(['course', 'section', 'quiz', 'questions', 'question_count', 'letters']);

form(QuestionForm::class);

mount(function (Course $course,CourseSection $section, Quiz $quiz){
    $this->letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    $this->course = $course;
    $this->section = $section;
    $this->quiz = $quiz;
    $this->questions = [];
    $this->question_count = $quiz->questions->count() == 0 ? 1 : $quiz->questions->count();
    $this->form->setModel($quiz);
    $this->dispatch('init-tinymce');
});

$add_question = function () {
    $this->question_count++;
    $this->dispatch('add-question');
};

$change_multiple_choice_value = function ($i, $n){
    for ($j= 0; $j < $this->form->questions[$i]['option']; $j++) { 
        $this->form->questions[$i]['answers'][$j]['is_true'] = $j == $n ? 1 : 0;
    }
};

$handle_change_question_type = function($val, $i){
    if($val == 'multiple-choice'){
        $this->form->questions[$i]['option'] = 5;
        $this->form->questions[$i]['answers'] = [];
    }
    if($val == 'option'){
        $this->form->questions[$i]['option'] = 2;
        $this->form->questions[$i]['answers'] = [];
    }
    $this->dispatch('init-tinymce');
};

$submit = function (){
    try{
        $this->form->store();
        $this->redirect("/course/{$this->course->shortname}/activity/update/quiz/instance/{$this->quiz->id}/section/{$this->section->section}", navigate: true);
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
};

?>

<x-layouts.app>
    @volt
    <div x-data="" class="h-full overflow-y-auto relative">
        <div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button wire:navigate.hover path="{{ $path ?? '' }}" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >{{ $quiz->name }} - {{ $section->name }}</span></p>
            <h1 class="text-[#121212] text-xl font-semibold" >Buat Soal</h1>
        </div>

        <div class="px-8 pt-8 pb-10 transition-all duration-300 space-y-6" >
            @for ($i = 0; $i < $question_count; $i++)
            <div class="border-l-primary border-l-4 bg-white py-5 px-6 rounded-xl" >
                <p class="block label text-gray-600  text-sm font-semibold mb-4" >Jenis Soal</p>
                <div class="flex space-x-9" >
                    <label for="choice_{{ $i }}" class="flex items-center mb-4" >
                        <input wire:model.live="form.questions.{{ $i }}.type" wire:change="handle_change_question_type($event.target.value, {{ $i }})" value="multiple-choice" name="question_type_{{ $i }}" id="choice_{{ $i }}" type="radio" class="radio">
                        <span class="font-medium text-sm text-grey-700 ml-2" >Pilihan Ganda</span>
                    </label>
                    <label for="option_{{ $i }}" class="flex items-center mb-4" >
                        <input wire:model.live="form.questions.{{ $i }}.type" wire:change="handle_change_question_type($event.target.value, {{ $i }})" value="option" name="question_type_{{ $i }}" id="option_{{ $i }}" type="radio" class="radio">
                        <span class="font-medium text-sm text-grey-700 ml-2" >Benar / Salah</span>
                    </label>
                    <label for="essay_{{ $i }}" class="flex items-center mb-4" >
                        <input wire:model.live="form.questions.{{ $i }}.type" wire:change="handle_change_question_type($event.target.value, {{ $i }})" value="essay" name="question_type_{{ $i }}" id="essay_{{ $i }}" type="radio" class="radio">
                        <span class="font-medium text-sm text-grey-700 ml-2" >Essay</span>
                    </label>
                </div>

                <div class="space-y-4" >

                    @if (!empty($form->questions[$i]['type']))
                    <label for="description">
                        <span class="block label text-gray-600 text-[12px] mb-1" >Masukkan Soal</span>
                        <div wire:ignore >
                            <textarea class="question_{{ $i }}" >{{ $form->questions[$i]['question'] }}</textarea>
                        </div>
                        @error('form.questions.{{ $i }}.question')
                        <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                        @enderror
                    </label>
                    @endif

                    @if (isset($form->questions[$i]) &&($form->questions[$i]['type'] == 'multiple-choice'))
                    <div class="grid grid-cols-4 space-x-4">
                        <label for="answer_option_count_{{ $i }}" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Opsi Jawaban</span>
                            <select wire:model.live="form.questions.{{ $i }}.option" id="answer_option_count_{{ $i }}" class="text-field" >
                                <option value="3" >3</option>
                                <option value="4" >4</option>
                                <option value="5" >5</option>
                            </select>
                        </label>
                        <label for="point_{{ $i }}" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Point</span>
                            <input wire:model.live="form.questions.{{ $i }}.point" type="number" id="point_{{ $i }}" placeholder="Masukkan jumlah point"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                            @error('form.questions.{{ $i }}.point')
                            <span class="text-error mt-3 text-sm" >{{ $message ?? 's' }}</span>
                            @enderror
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4 grid-flow-row">
                        @for ($n = 0; $n <  $form->questions[$i]['option']; $n++)
                        <div class="text-field flex items-center gap-x-2" >
                            <input wire:change="change_multiple_choice_value('{{ $i }}', '{{ $n }}')" wire:model="form.questions.{{ $i }}.answers.{{ $n }}.is_true" value="1" name="question_{{ $i }}_answer" id="" type="radio" class="radio m-0">
                            <span class="font-medium" >
                                {{ $letters[$n] }}.
                            </span>
                            <input wire:model.live="form.questions.{{ $i }}.answers.{{ $n }}.answer" placeholder="Option" type="text" class="text-field-base placeholder:font-medium w-full" >
                        </div>
                        @endfor
                    </div>
                    @endif

                    @if (isset($form->questions[$i]) &&($form->questions[$i]['type'] == 'option'))
                    <div class="grid grid-cols-2 space-x-4">
                        <label for="point_{{ $i }}" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Point</span>
                            <input wire:model.live="form.questions.{{ $i }}.point" type="number" id="point_{{ $i }}" placeholder="Masukkan jumlah point"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                            @error('form.questions.{{ $i }}.point')
                            <span class="text-error mt-3 text-sm" >{{ $message ?? 's' }}</span>
                            @enderror
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4 grid-flow-row">
                        <div class="text-field flex items-center gap-x-2" >
                            <input wire:change="change_multiple_choice_value('{{ $i }}', 0)" wire:model="form.questions.{{ $i }}.answers.0.is_true" value="1" name="question_{{ $i }}_answer" id="" type="radio" class="radio m-0">
                            <input wire:model="form.questions.{{ $i }}.answers.0.answer" placeholder="Option" type="text" class="text-field-base placeholder:font-medium w-full" >
                        </div>
                        <div class="text-field flex items-center gap-x-2" >
                            <input wire:change="change_multiple_choice_value('{{ $i }}', 1)" wire:model="form.questions.{{ $i }}.answers.1.is_true" value="1" name="question_{{ $i }}_answer" id="" type="radio" class="radio m-0">
                            <input wire:model="form.questions.{{ $i }}.answers.1.answer" placeholder="Option" type="text" class="text-field-base placeholder:font-medium w-full" >
                        </div>
                    </div>
                    @endif

                    @if (isset($form->questions[$i]) &&($form->questions[$i]['type'] == 'essay'))
                    <div class="grid grid-cols-2 space-x-4">
                        <label for="point_{{ $i }}" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Point</span>
                            <input wire:model.live="form.questions.{{ $i }}.point" type="number" id="point_{{ $i }}" placeholder="Masukkan jumlah point"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                            @error('form.questions.{{ $i }}.point')
                            <span class="text-error mt-3 text-sm" >{{ $message ?? 's' }}</span>
                            @enderror
                        </label>
                    </div>

                    <label for="description" class="block mt-6" >
                        <span class="block label text-gray-600 text-[12px] mb-1" >Masukkan Jawaban</span>
                        <div wire:ignore >
                            <textarea class="answer_{{ $i }}" >{{ $form->questions[$i]['answers'][0]['answer'] }}</textarea>
                        </div>
                    </label>
                    @endif

                </div>

            </div>
            @endfor
            <x-button
                variant="light"
                wire:click="add_question"
            >
                Tambah Soal
            </x-button>

            <div class="flex justify-end gap-3 mt-4" >
                <x-button @click="$store.alert.save = true" type="button" >
                    Submit
                </x-button>
                <x-button @click="$store.alert.cancel = true" variant="outlined" >
                    Batal
                </x-button>
            </div>
        </div>                

        <x-alert
            show="$store.alert.cancel"
            onCancel="$store.alert.cancel = false"
            type="warning"
            title="Batal"
            message="Batalkan pembuatan aktivitas ?"
        />
        
        <x-alert
            show="$store.alert.save"
            onCancel="$store.alert.save = false"
            type="success"
            title="Batal"
            message="Simpan soal ?"
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

        Alpine.data('collapse', (initialState = true) => ({
            expand: initialState,
            toggle() {
                this.expand = ! this.expand
            }
        }))

        $wire.$on('init-tinymce', () => {
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

                            if(tinymce.activeEditor.targetElm.classList[0].includes('question')){
                                const idx = tinymce.activeEditor.targetElm.classList[0].split('_')[1]
                                $wire.$set(`form.questions.${idx}.question`, tinymce.activeEditor.getContent())
                            } else {
                                const idx = tinymce.activeEditor.targetElm.classList[0].split('_')[1]
                                $wire.$set(`form.questions.${idx}.answers.0.answer`, tinymce.activeEditor.getContent())
                                $wire.$set(`form.questions.${idx}.answers.0.is_true`, 1)
                            }

                        })
                    }
                });
            }, 10);
        })

    </script>
    @endscript

    @endvolt
</x-layouts.app>