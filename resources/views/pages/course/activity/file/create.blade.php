<?php

use function Livewire\Volt\{state, mount, on, form, updated, usesFileUploads};
use App\Livewire\Forms\Activity\ResourceForm;
use App\Models\{
    Course,
    CourseSection
};

usesFileUploads();

state(['course', 'section']);
form(ResourceForm::class);
mount(function (Course $course,CourseSection $section){
    $this->course = $course;
    $this->section = $section;
    $this->form->setModel($course);
    $this->form->setSection($section->section);
});

updated([
    'form.fileResource' => function (){
        Log::info($this->form->fileResource);
    }
]);

$submit = function (){

    $this->form->validate();
    
    try {
        $this->form->store();
        $this->redirect('/course/'.$this->course->shortname, navigate: true);
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
};

$deleteFile = function ($id){
  array_splice($this->form->fileResource, $id, 1);
};

?>

<x-layouts.app>
    @volt
    <div x-data="" class="h-full overflow-y-auto relative">
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Tambah File"
            :course="$course"
            :section="$section"
        />

        <form wire:submit="submit">
            <div class="px-8 pt-8 pb-10 transition-all duration-300 space-y-4" >
    
                <x-collapse
                    title="Umum"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-2 gap-x-7">
                        <label for="urlname" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Nama</span>
                            <input wire:model="form.name" type="text" id="urlname" placeholder="Masukkan Nama"  class="text-field">
                            @error('form.name')
                            <span class="text-error mt-3 text-sm" >{{ $message ?? 's' }}</span>
                            @enderror
                        </label>
                    </div>
                    <div class="mt-3">
                        <label for="description">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Deskripsi</span>
                            <div wire:ignore >
                                <textarea></textarea>
                            </div>
                            <input type="hidden" name="intro" />
                            @error('form.description')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Berkas"
                    x-data="collapse"
                    x-show="expand"
                >
                    <label for="file" class="bg-grey-100 flex justify-center py-6 rounded-lg relative" >
                        <div class="flex items-center" >
                            <img src="{{ asset('assets/icons/upload-file.svg') }}" alt=""/>
                            <div class="ml-4" >
                                <p class="font-medium mb-1" >Drag and drop a file here or click</p>
                                <p class="text-grey-500 text-sm" >(pdf, docx, pptx, xlsx <b>Maximum 3MB</b>)</p>
                            </div>
                        </div>
                        <input multiple wire:model.live="form.fileResource" name="files" id="file" type="file" class="invisible absolute" />
                    </label>
                    <div class="flex flex-col mt-4" >
                        @foreach ($form->fileResource ?? [] as $i => $item)
                        <div class="flex items-center px-4 py-2 bg-grey-100 rounded-lg mb-3" >
                            <img class="w-7 mr-4" src="{{ asset('assets/icons/pdf.svg') }}" >
                            <div>
                                <p class="font-semibold text-sm mb-[2px]" >{{ $item->getClientOriginalName() }}</p>
                                <p class="text-xs text-grey-500"  >{{ $item->getSize() }}</p>
                            </div>
                            <span class="ml-auto" wire:click="deleteFile('{{ $i }}')" >X</span>
                        </div>
                        @endforeach
                    </div>
                </x-collapse>
    
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button type="submit" >
                        Submit
                    </x-button>
                    <x-button @click="$store.alert.cancel = true" variant="outlined" >
                        Batal
                    </x-button>
                </div>
    
            </div>
        </form>

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

        tinymce.init({
            selector: 'textarea',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            setup: editor => {
                editor.on('change', e => {
                    document.querySelector('input[type=hidden]').value = tinymce.activeEditor.getContent()
                    $wire.$set('form.description', tinymce.activeEditor.getContent())
                })
            }
        });

    </script>
    @endscript

    @endvolt
</x-layouts.app>