<?php

use function Livewire\Volt\{state, mount, on, form, updated, usesFileUploads};
use App\Livewire\Forms\Activity\AssignmentForm;
use App\Models\{
    Course,
    CourseSection,
    AssignmentFile,
};

usesFileUploads();
state(['course', 'section']);
form(AssignmentForm::class);
mount(function (Course $course,CourseSection $section){
    $this->course = $course;
    $this->section = $section;
    $this->form->setModel($course);
    $this->form->setSection($section->section);
});

$handle_change_due_date_type = function ($e){
    Log::info($e);

    if($e == 'time'){
        $this->form->fill([
            'start_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'due_date' => \Carbon\Carbon::now()->format('Y-m-d'),
        ]);
    }

};

$submit = function (){

    $this->form->validate();
    
    try {
        $this->form->store();
        $this->redirect('/course/'.$this->course->shortname, navigate: true);
    } catch (\Throwable $th) {
        throw $th;
        // Log::info($th->getMessage());
    }
};

updated([
    'form.file' => function (){
        foreach ($this->form->file as $file) {
            $this->form->files[] = $file;
        }
    }
]);

$deleteFile = function ($id){
  array_splice($this->form->files, $id, 1);
};

$deleteOldFile = function ($file){
    try {
        AssignmentFile::destroy($file['id']);
        Storage::delete($file['path']);
        $this->form->oldFiles = $this->form->assignment->files;
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
};

?>

<x-layouts.app>
    @volt
    <div x-data="" class="h-full overflow-y-auto relative">
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Tambah Penugasan"
            :course="$course"
            :section="$section"
            hold="true"
            onclick="$store.alert.cancel = true"
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
                    title="Upload Berkas"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div >
                        <label for="file" class="bg-grey-100 flex justify-center py-6 rounded-md" >
                            <div class="flex items-center" >
                                <img src="{{ asset('assets/icons/upload-file.svg') }}" alt=""/>
                                <div class="ml-4" >
                                    <p class="font-medium mb-1" >Drag and drop a file here or click</p>
                                    <p class="text-grey-500 text-sm" >(pdf, docx, pptx, xlsx <b>Maximum 3MB</b>)</p>
                                </div>
                            </div>
                            <input multiple wire:model.live="form.file" name="files" id="file" type="file" class="invisible absolute" />
                        </label>
                        <div class="flex flex-col mt-4" >
                            @foreach ($form->oldFiles ?? [] as $i => $item)
                            <div class="flex items-center px-4 py-2 bg-grey-100 rounded-lg mb-3" >
                                <img class="w-7 mr-4" src="{{ asset('assets/icons/pdf.svg') }}" >
                                <div>
                                    <p class="font-semibold text-sm mb-[2px]" >{{ $item->name}}</p>
                                    <p class="text-xs text-grey-500"  >{{ $item->size }}</p>
                                </div>
                                <span class="ml-auto cursor-pointer" wire:confirm="Yakin ingin hapus file?" wire:click="deleteOldFile({{ $item }})" >X</span>
                            </div>
                            @endforeach
                            @foreach ($form->files ?? [] as $i => $item)
                            <div class="flex items-center px-4 py-2 bg-grey-100 rounded-lg mb-3" >
                                <img class="w-7 mr-4" src="{{ asset('assets/icons/pdf.svg') }}" >
                                <div>
                                    <p class="font-semibold text-sm mb-[2px]" >{{ $item->getClientOriginalName() }}</p>
                                    <p class="text-xs text-grey-500"  >{{ $item->getSize() }}</p>
                                </div>
                                <span class="ml-auto cursor-pointer" wire:confirm="Yakin ingin hapus file?" wire:click="deleteFile('{{ $i }}')" >X</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Waktu Pengerjaan"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div>
                        <span class="block label text-gray-600 mb-4 mt-3" >Pilih Berdasarkan</span>
                        <div class="grid grid-cols-4" >
                            <label for="date" class="flex items-center mb-4" >
                                <input wire:change="handle_change_due_date_type($event.target.value)" wire:model="form.due_date_type" value="date" name="duedatetype" id="date" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Tanggal</span>
                            </label>
                            <label for="time" class="flex items-center mb-4" >
                                <input wire:change="handle_change_due_date_type($event.target.value)" wire:model="form.due_date_type" value="time" name="duedatetype" id="time" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Waktu/Jam</span>
                            </label>
                        </div>
                        @error('form.due_date_type')
                            <span class="text-error text-sm mb-9" >{{ $message }}</span>
                        @enderror
                    </div>
                    <div
                        class="grid grid-cols-2 gap-x-7 gap-y-3"
                    >
                        <label for="stardate" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal Dimulai</span>
                            <input @readonly($form->due_date_type == 'time') wire:model="form.start_date" name="startdate" type="date" id="startdate" placeholder="Masukkan Nama"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                        </label>
                        <label for="starttime" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu</span>
                            <input wire:model="form.start_time" name="starttime" type="time" id="starttime" placeholder="Masukkan Nama"  class="text-field">
                        </label>
                        <label for="enddate" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal Berakhir</span>
                            <input @readonly($form->due_date_type == 'time') wire:model="form.due_date" name="enddate" type="date" id="enddate" placeholder="Masukkan Nama"  class="text-field">
                            @error('form.due_date')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                        <label for="endtime" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu</span>
                            <input wire:model="form.due_time" name="endtime" type="time" id="endtime" placeholder="Masukkan Nama"  class="text-field">
                            @error('form.due_time')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Jenis Pengiriman"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div>
                        <span class="block label text-gray-600 mb-2 mt-3" >Pilih Jenis</span>
                        <div class="grid grid-cols-4" >
                            <label for="online_submission_type" class="flex items-center mb-4" >
                                <input wire:model="form.submission_type" value="onlinetext" name="submission_type" id="online_submission_type" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Text Daring</span>
                            </label>
                            <label for="file_submission_type" class="flex items-center mb-4" >
                                <input wire:model="form.submission_type" value="file" name="submission_type" id="file_submission_type" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >File</span>
                            </label>
                        </div>
                        @error('form.submission_type')
                            <span class="text-error text-sm mb-9" >{{ $message }}</span>
                        @enderror
                    </div>
                    <div
                        class="grid grid-cols-3 gap-x-7 gap-y-3 mb-4"
                    >
                        <label x-show="$wire.form.submission_type == 'onlinetext'" for="wordlimit" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah maksimum kata</span>
                            <input wire:model="form.word_limit" name="assignsubmission_onlinetext_wordlimit" type="number" id="wordlimit" placeholder="Masukkan jumlah maksimum kata"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                        </label>
                        <label x-show="$wire.form.submission_type == 'file'" for="wordlimit" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Jumlah maksimum file</span>
                            <select wire:model="form.max_size" name="assignsubmission_file_maxsizebytes" class="text-field" >
                                <option value="" >-- Pilih Jumlah Maksimal --</option>
                                <option value="3145728" >3 mb</option>
                                <option value="2097152" >2 mb</option>
                                <option value="1048576" >1 mb</option>
                                <option value="512000" >500 kb</option>
                                <option value="204800" >200 kb</option>
                            </select>
                        </label>
                        <label x-show="$wire.form.submission_type == 'file'" for="wordlimit" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tipe Dokumen</span>
                            <select wire:model="form.file_types" name="assignsubmission_file_filetypeslist" class="text-field" >
                                <option value="" >-- Pilih Tipe --</option>
                                <option value="document" >Document (.pdf, .docs, .pptx, dll)</option>
                                <option value="image" >Gambar (.png, .jpg, .jpeg, dll)</option>
                                <option value="*" >Semua</option>
                            </select>
                        </label>
                    </div>
                </x-collapse>
    
                <x-collapse
                    title="Pengingat Aktivitas"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div
                        class="grid grid-cols-3 gap-x-7 gap-y-3"
                    >
                        <label for="remember" class="" >
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal </span>
                            <input wire:model="form.activity_remember" name="" type="date" id="remember"  class="ring-1 text-sm ring-gray-300 py-2 rounded-xl px-3 w-full bg-grey-100 focus-within:ring-primary focus-within:ring-2 transition-all box-border focus:outline-none placeholder:text-grey-400 placeholder:font-medium">
                        </label>
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

        <x-alert
            show="$store.alert.cancel"
            onCancel="$store.alert.cancel = false"
            onOk="$wire.submit()"
            type="warning"
            title="Batal"
            message="Batalkan pembuatan aktivitas ?"
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

        window.addEventListener("beforeunload", function(event) {
            event.preventDefault()
            event.returnValue = '';
        }, { capture: true });

    </script>
    @endscript
    @endvolt
</x-layouts.app>