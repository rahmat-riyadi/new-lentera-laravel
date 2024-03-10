<?php

use function Livewire\Volt\{state, mount, on, form};
use App\Livewire\Forms\Quiz\QuestionForm;
use App\Models\{
    Course,
    CourseSection,
    Quiz,
};

state(['course', 'section', 'quiz', 'questions', 'question_count']);

form(QuestionForm::class);

mount(function (Course $course,CourseSection $section, Quiz $quiz){
    $this->course = $course;
    $this->section = $section;
    $this->quiz = $quiz;
    $this->questions = [];
    $this->question_count = 1;
});

$add_question = function () {
    $this->question_count++;
    $this->dispatch('add-question');
};

$handle_change_question_type = function($val, $i){
    if($val == 'multiple-choice'){
        $this->form->questions[$i]['option'] = 5;
    }
};

$submit = function (){
    $this->form->store();
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
                    <label for="description">
                        <span class="block label text-gray-600 text-[12px] mb-1" >Masukkan Soal</span>
                        <div wire:ignore >
                            <textarea class="question_{{ $i }}" ></textarea>
                        </div>
                        <input type="hidden" name="intro" />
                        @error('form.description')
                            <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                        @enderror
                    </label>
                    
                    {{-- @if ($form->questions[$i]['type'] == 'multiple-choice') --}}
                    {{-- <div class="grid grid-cols-4 space-x-4">
                        <label for="answer_option_count_{{ $i }}" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Opsi Jawaban</span>
                            <select wire:model.live="form.questions.{{ $i }}.option" id="answer_option_count_{{ $i }}" class="text-field" >
                                <option value="3" >3</option>
                                <option value="4" >4</option>
                                <option selected value="5" >5</option>
                            </select>
                        </label>
                        <label for="point_{{ $i }}" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah Point</span>
                            <input type="text" id="point_{{ $i }}" placeholder="Masukkan jumlah point"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        @for ($n = 0; $n <  $form->questions[$i]['option']; $n++)
                        <div class="text-field flex items-center gap-x-2" >
                            <input wire:change="" wire:model="" value="option" name="" id="" type="radio" class="radio m-0">
                            <span class="font-medium" >
                                A.
                            </span>
                            <input placeholder="Option" type="text" class="text-field-base placeholder:font-medium" >
                        </div>
                        @endfor
                    </div> --}}
                    {{-- @endif --}}

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
                <x-button wire:click="submit" type="button" >
                    Submit
                </x-button>
                <x-button @click="$store.alert.cancel = true" variant="outlined" >
                    Batal
                </x-button>
            </div>
        </div>                
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

        Livewire.on('add-question', ([ id ]) => {
            console.log(id)
            setTimeout(() => {
                tinymce.init({
                    height: 280,
                    menubar: false,
                    selector: 'textarea',
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                    file_picker_types: 'file image media',
                    images_upload_url: '/api/question/image',
                    file_picker_callback: (cb, value, meta) => {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');

                        input.addEventListener('change', (e) => {
                        const file = e.target.files[0];

                        const reader = new FileReader();
                        reader.addEventListener('load', () => {
                            /*
                            Note: Now we need to register the blob in TinyMCEs image blob
                            registry. In the next release this part hopefully won't be
                            necessary, as we are looking to handle it internally.
                            */
                            const id = 'blobid' + (new Date()).getTime();
                            const blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                            const base64 = reader.result.split(',')[1];
                            const blobInfo = blobCache.create(id, file, base64);
                            blobCache.add(blobInfo);

                            /* call the callback and populate the Title field with the file name */
                            cb(blobInfo.blobUri(), { title: file.name });
                        });
                        reader.readAsDataURL(file);
                        });

                        input.click();
                    },
                    setup: editor => {
                        editor.on('change', e => {
                            const idx = tinymce.activeEditor.targetElm.classList[0].split('_')[1]
                            console.log(idx)
                            $wire.$set(`form.questions.${idx}.question`, tinymce.activeEditor.getContent())
                        })
                    }
                });
            }, 10);
        })

        tinymce.init({
            height: 280,
            menubar: false,
            selector: 'textarea',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            file_picker_types: 'file image media',
            images_upload_url: '/api/question/image',
            file_picker_callback: (cb, value, meta) => {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');

                input.addEventListener('change', (e) => {
                const file = e.target.files[0];

                const reader = new FileReader();
                reader.addEventListener('load', () => {
                    /*
                    Note: Now we need to register the blob in TinyMCEs image blob
                    registry. In the next release this part hopefully won't be
                    necessary, as we are looking to handle it internally.
                    */
                    const id = 'blobid' + (new Date()).getTime();
                    const blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    const base64 = reader.result.split(',')[1];
                    const blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);

                    /* call the callback and populate the Title field with the file name */
                    cb(blobInfo.blobUri(), { title: file.name });
                });
                reader.readAsDataURL(file);
                });

                input.click();
            },
            setup: editor => {
                editor.on('change', e => {
                    // document.querySelector('input[type=hidden]').value = tinymce.activeEditor.getContent()
                    const idx = tinymce.activeEditor.targetElm.classList[0].split('_')[1]
                    console.log(idx)
                    $wire.$set(`form.questions.${idx}.question`, tinymce.activeEditor.getContent())
                })
            }
        });

    </script>
    @endscript

    @endvolt
</x-layouts.app>