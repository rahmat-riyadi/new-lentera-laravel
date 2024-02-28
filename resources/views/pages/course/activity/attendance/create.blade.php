<?php

use function Livewire\Volt\{state, mount, on, form, updated, usesFileUploads};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection
};

usesFileUploads();

state(['course', 'section']);
form(AttendanceForm::class);
mount(function (Course $course,CourseSection $section){
    $this->course = $course;
    $this->section = $section;
    $this->form->setModel($course);
    $this->form->setSection($section->section);
});

$submit = function (){
    $this->form->validate();
    try {
        $this->form->store();
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
        throw $th;
    }  
};

?>
<x-layouts.app>
    @volt
    <div x-data class="h-full overflow-y-auto relative">
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Tambah Kehadiran"
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
                    title="Waktu Kehadiran"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-3 gap-x-7">
                        <label for="date">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Tanggal</span>
                            <input wire:model="form.date" name="date" type="date" id="date" placeholder="Masukkan URL"  class="text-field">
                            @error('form.date')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                        <label for="timeStart">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu mulai</span>
                            <input wire:model="form.starttime" name="startTime" type="time" id="timeStart" placeholder="Masukkan URL"  class="text-field">
                            @error('form.starttime')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                        <label for="timeEnd">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu akhir</span>
                            <input  wire:model="form.endtime" name="endTime" type="time" id="timeEnd" placeholder="Masukkan URL"  class="text-field">
                            @error('form.endtime')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </x-collapse>

                <x-collapse
                    title="Ulang Sesi"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div class="grid grid-cols-3 gap-x-7">
                        <div>
                            <span class="block label text-gray-600 mb-2" >Apakah anda ingin mengulang sesi</span>
                            <label for="remember" class="flex items-center" >
                                <input type="checkbox" name="repeat" class="checkbox w-[18px] h-[18px]" id="remember">
                                <span class="text-sm ml-2" >Ulang Sesi</span>
                            </label>
                        </div>
                        <label for="timeStart">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Ulang berapa kali</span>
                            <input class="text-field" name="repetitionAttempt" type="number" />
                            <span class="peer-has-[:focus:invalid]:inline-block hidden text-error text-sm mt-1" >Url Harus Diisi</span>
                        </label>
                    </div>
                </x-collapse>

                <x-collapse
                    title="Pengisi Kehadiran"
                    x-data="collapse"
                    x-show="expand"
                >
                    <div>
                        <span class="block label text-gray-600 mb-4 mt-3" >Kehadiran diisi oleh</span>
                        <div class="grid grid-cols-6" >
                            <label for="filledByDosen" class="flex items-center mb-4" >
                                <input wire:model="form.filled_by" value="Teacher" name="filledBy" id="filledByDosen" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Dosen</span>
                            </label>
                            <label for="filledByMahasiswa" class="flex items-center mb-4" >
                                <input wire:model="form.filled_by" value="Student" name="filledBy" id="filledByMahasiswa" type="radio" class="radio">
                                <span class="font-medium text-sm text-grey-700 ml-2" >Mahasiswa</span>
                            </label>
                        </div>
                        @error('form.filled_by')
                            <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                        @enderror
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