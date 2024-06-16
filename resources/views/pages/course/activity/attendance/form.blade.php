<?php

use function Livewire\Volt\{state, mount, on, form};
use App\Livewire\Forms\Attendance\FillForm;
use App\Models\{
    Course,
    CourseSection,
    Attendance,
    User,
    StudentAttendance,
    CourseModule,
};

form(FillForm::class);
state(['course', 'section', 'attendance', 'students', 'courseModule']);

mount(function (Course $course,CourseSection $section, Attendance $attendance, CourseModule $courseModule, $session){
    $this->course = $course;
    $this->section = $section;
    $this->attendance = $attendance;
    $this->courseModule = $courseModule;
    $this->form->setModel($attendance, $courseModule, $session);
});

$submit = function (){
    try {
        $this->form->submit();  
        $this->dispatch('notify', 'success', 'Absen berhasil disimpan');
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
        $this->dispatch('notify', 'error', 'Terjadi Kesalahan');
    }
};

?>

<x-layouts.app>
    @volt
    <div x-data class="h-full overflow-y-auto relative">
        <div class=" bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button @click="$store.alert.cancel = true" path="javascript:;" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> {{ $course->fullname }} <span class="mx-2 text-[9px]" > >> </span>  <span >Detail Kehadiran - {{ $section->name }}</span> <span class="mx-2 text-[9px]" > >> </span> <span class="text-[#121212]" >Lakukan Kehadiran </span></p>
            <h1 class="text-[#121212] text-xl font-semibold" >Lakukan Kehadiran</h1>
        </div>

        <form wire:submit="submit">
            <div class="p-8" >

                <div class="bg-white py-3 px-5 flex gap-x-5 mb-5">
                    <span>
                        <span class="chip attend px-2 py-1 mr-1" >H</span>
                        <span class="font-medium text-sm" >Hadir</span>
                    </span>
                    <span>
                        <span class="chip sick px-2 py-1 mr-1" >S</span>
                        <span class="font-medium text-sm" >Sakit</span>
                    </span>
                    <span>
                        <span class="chip late px-2 py-1 mr-1" >T</span>
                        <span class="font-medium text-sm" >Terlambat</span>
                    </span>
                    <span>
                        <span class="chip assignment px-2 py-1 mr-1" >I</span>
                        <span class="font-medium text-sm" >Izin</span>
                    </span>
                    <span>
                        <span class="chip absen px-2 py-1 mr-1" >A</span>
                        <span class="font-medium text-sm" >Alpa</span>
                    </span>
                </div>

                <div class="bg-white p-4" >
                    <table class=" w-full"  >
                        <thead class="table-head" >
                            <tr>
                                <td class="w-14" >No.</td>
                                <td class="w-[280px]" >Mahasiswa</td>
                                <td class="text-center" >H</td>
                                <td class="text-center" >I</td>
                                <td class="text-center" >S</td>
                                <td class="text-center" >A</td>
                                <td class="text-center" >T</td>
                                <td class="w-[260px] pr-3 pl-6" >Catatan</td>
                            </tr>
                        </thead>
                        <tbody class="table-body" >
                            <tr>
                                <td></td>
                                <td>Lakukan Pengisian Otomatis:</td>
                                <td class="text-center" >
                                    <input value="Hadir" name="all_status" id="date" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input value="Izin" name="all_status" id="date" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input value="Sakit" name="all_status" id="date" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input value="Alpa" name="all_status" id="date" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input value="Tanpa Keterangan" name="all_status" id="date" type="radio" class="radio">
                                </td>
                                <td></td>
                            </tr>
                            @foreach ($form->students as $i => $student)
                            <tr id="studentRow" >
                                <td>{{ $i+1 }}</td>
                                <td>
                                    <div class="flex items-center" >
                                        <img src="{{ $student['picture'] }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                        <div>
                                            <p class="mb-1">{{ $student['name'] }}</p>
                                            <span class="text-grey-500 " >{{ $student['nim'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" >
                                    <input wire:model="form.students.{{ $i }}.status" value="Hadir" name="{{ $student['id'] }}_status" id="student_status" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input wire:model="form.students.{{ $i }}.status" value="Izin" name="{{ $student['id'] }}_status" id="student_status" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input wire:model="form.students.{{ $i }}.status" value="Sakit" name="{{ $student['id'] }}_status" id="student_status" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input wire:model="form.students.{{ $i }}.status" value="Alpa" name="{{ $student['id'] }}_status" id="student_status" type="radio" class="radio">
                                </td>
                                <td class="text-center" >
                                    <input wire:model="form.students.{{ $i }}.status" value="Terlambat" name="{{ $student['id'] }}_status" id="student_status" type="radio" class="radio">
                                </td>
                                <td class="pr-3 pl-6" >
                                    <input wire:model="form.students.{{ $i }}.note" name="{{ $student['id'] }}_note" class="text-field" />
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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

        <x-toast/>

    </div>

    @script
    <script>
    
        Alpine.store('alert', {
            show: false,
            type: 'warning',
            toggle() {
                this.show = ! this.show
            }
        })

        Livewire.on('notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })

        window.addEventListener("beforeunload", function(event) {
            event.preventDefault()
            event.returnValue = '';
        }, { capture: true });
        
    </script>
    @endscript
    
    @endvolt

    
</x-layouts.app>