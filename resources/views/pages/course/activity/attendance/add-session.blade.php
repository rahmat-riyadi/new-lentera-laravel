<?php

use function Livewire\Volt\{state, mount, form};
use App\Livewire\Forms\Attendance\SessionForm;
use App\Models\{
    Course,
    CourseSection,
    Attendance,
    User,
    StudentAttendance,
    Context,
};

form(SessionForm::class);

state(['course', 'section', 'attendance', 'sessions']);

mount(function (Course $course,CourseSection $section, Attendance $attendance){
    $this->form->attendance = $attendance;
});

$submit = function (){
    $this->form->validate();
    try {
        $this->form->store();
        $this->redirect('/teacher/attendance/session/'.$this->attendance->id, navigate: true);
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
        throw $th;
    }  
};

?>

<x-layouts.app>
    @volt
    <div x-data class="h-full overflow-y-auto relative">
        <div class="bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button @click="$store.alert.cancel = true" path="javascript:;" />
            <p class="text-sm text-[#656A7B] font-[400] flex flex-wrap leading-7 items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span class="" >Kehadiran - {{ $section->name }}</span>  <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Tambah Sesi </span></p>
            <h1 class="text-[#121212] text-xl font-semibold" >Tambah Sesi - {{ $section->name }}</h1>
        </div>

        <form wire:submit="submit">
            <div class="px-8 pt-8 pb-10 transition-all duration-300 space-y-4" >
    
                <x-collapse
                    title="Umum"
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
                            <input wire:model="form.time_start" name="startTime" type="time" id="timeStart" placeholder="Masukkan URL"  class="text-field">
                            @error('form.time_start')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                        <label for="timeEnd">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Waktu akhir</span>
                            <input  wire:model="form.time_end" name="endTime" type="time" id="timeEnd" placeholder="Masukkan URL"  class="text-field">
                            @error('form.time_end')
                                <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </x-collapse>

                {{-- <x-collapse
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
                                <input value="1" wire:model="form.is_repeat" type="checkbox" name="repeat" class="checkbox w-[18px] h-[18px]" id="remember">
                                <span class="text-sm ml-2" >Ulang Sesi</span>
                            </label>
                        </div>
                        <label for="timeStart">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Ulang berapa kali</span>
                            <input wire:model="form.repeat_attempt" class="text-field" name="repetitionAttempt" type="number" />
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
                </x-collapse> --}}

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

        

    </script>
    @endscript

    @endvolt
</x-layouts.app>