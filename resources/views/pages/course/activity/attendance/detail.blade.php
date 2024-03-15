<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    Attendance,
    User,
    StudentAttendance,
    Context,
};

state(['course', 'section', 'attendance', 'students', 'role', 'student_status', 'status', 'note']);

mount(function (Course $course,CourseSection $section, Attendance $attendance){
    $ctx = Context::where('contextlevel', 50)->where('instanceid', 4)->first();
    $data = DB::connection('moodle_mysql')->table('mdl_role_assignments as ra')
    ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
    ->where('ra.contextid', $ctx->id)
    ->where('ra.userid', auth()->user()->id)
    ->select(
        'r.shortname as role',
    )
    ->first();
    $this->role = $data->role;
    $this->course = $course;
    $this->section = $section;

    if($this->role != 'student'){
        $this->attendance = $attendance;
        $studentAttendances = StudentAttendance::where('attendance_id', $attendance->id)->get();
        $studentAttendancesIds = $studentAttendances->pluck('student_id')->toArray();
        $students = User::whereIn('id', $studentAttendancesIds)->get();
        $this->students = $students->map(function ($e) use ($studentAttendances) {
            $att = $studentAttendances->firstWhere('studentId', $e->id);
            return [
                'id' => $e->id,
                'name' => $e->firstname . ' ' . $e->lastname,
                'nim' => $e->username,
                'status' => $att->status ?? null,
                'notes' => $att->notes ?? null,
            ];
        });
    } else {
        $this->student_status = StudentAttendance::where('attendance_id', $attendance->id)
        ->where('student_id', auth()->user()->id)
        ->first();
        $this->status = $this->student_status->status;
        $this->note = $this->student_status->note;
    }

});

$submit_attendance = function (){
    $this->validate([
        'status' => 'required',
    ],[
        'status.required' => 'Status harus diisi'
    ]);

    try {
        $this->student_status->update([
            'status' => $this->status,
            'note' => $this->note,
        ]);
        $this->dispatch('notify', 'success', 'Kehadiran berhasil diubah');
        $this->dispatch('close-modal');
    } catch (\Throwable $th) {
        $this->dispatch('notify', 'error', $th->getMessage());
        Log::info($th->getMessage());
    }


};  

?>

<x-layouts.app>
    @volt
    <div x-data="pages" >
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Detail Kehadiran"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-1" >{{ $attendance->name }}</h3>
                <p class="text-grey-700 text-sm" >{{{ $attendance->intro }}}</p>
                <table class="w-full font-medium" >
                    <tr>
                        <td style="width: 210px; height: 50px;" class="text-grey-500 text-sm" >Tenggat Waktu</td>
                        <td class="text-[#121212] text-sm" >: {{ Carbon\Carbon::parse($attendance->date)->translatedFormat('d F Y') }}, {{ $attendance->starttime }} - {{ $attendance->endtime }}</td>
                    </tr>
                    <tr>
                        <td style="width: 210px;" class="text-grey-500 text-sm" >Kehadiran dilakukan oleh</td>
                        <td class="text-[#121212] text-sm" >: {{ $attendance->filled_by == 'Student' ? 'Mahasiswa' : 'Dosen' }}</td>
                    </tr>
                </table>
                @if ($role != 'student')
                <div class="h-5" ></div>
                <a wire:navigate.hover href="/teacher/attendance/form/{{ $attendance->id }}" class="btn-medium btn-outlined" >
                    Lakukan Kehadiran
                </a>
                @endif
            </div>

            @if ($role != 'student')
            <div class="bg-white p-5 mt-6 rounded-xl">
                <table class=" w-full" >
                    <thead class="table-head" >
                        <tr>
                            <td class="" >No.</td>
                            <td class="" >Mahasiswa</td>
                            <td class="text-center" >Keterangan</td>
                            <td class="w-[170px]" >Catatan</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $i => $student)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <div class="flex items-center" >
                                    <img src="{{ ('assets/images/avatar.jpg') }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                    <div>
                                        <p class="mb-1">{{ $student['name'] }}</p>
                                        <span class="text-grey-500 " >{{ $student['nim'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center" >
                                @if($student['status'] === null)
                                    <span class="chip empty px-3" >.</span>
                                @else
                                    
                                @endif
                            </td>
                            <td>
                                -    
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="bg-white p-5 mt-6 rounded-xl" >
                <h3 class="font-semibold text-lg mb-1" >Status Absensi</h3>
                <table class="w-full font-medium" >
                    <tr>
                        <td style="width: 210px; height: 50px;" class="text-grey-500 text-sm" >Keterangan</td>
                        <td class="text-[#121212] text-sm" >:
                            @switch($student_status->status)
                                @case('Hadir')
                                <span class="chip attend px-3 py-1 text-xs" >Hadir</span>
                                    @break
                                @case('Izin')
                                    <span class="chip assignment px-3 py-1 text-xs" >Izin</span>
                                    @break
                                @case('Sakit')
                                    <span class="chip sick px-3 py-1 text-xs" >Sakit</span>
                                    @break
                                @case('Terlambat')
                                    <span class="chip sick px-3 py-1 text-xs" >Terlambat</span>
                                    @break
                                @case('Alpa')
                                    <span class="chip absen px-3 py-1 text-xs" >Alpa</span>
                                    @break
                                @case('Tanpa Keterangan')
                                    <span class="chip absen px-3 py-1 text-xs" >Tanpa Keterangan</span>
                                    @break
                                @default
                                <span class="chip empty px-3 py-1 text-xs" >belum ada status</span>
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 210px; " class="text-grey-500 text-sm" >Catatan</td>
                        <td class="text-[#121212] text-sm" >: {{ $student_status->note ?? '-'}}</td>
                    </tr>
                </table>
                @php
                    $now = \Carbon\Carbon::now();
                    $endtime = \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->endtime);
                    $starttime = \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->starttime);
                    Log::info($endtime->gt($now)) ;
                @endphp
                @if ($attendance->filled_by == 'Student' && $endtime->gt($now) && $starttime->lt($now) )
                <div class="h-5" ></div>
                <button type="button" @click="modal.student_attendance = true" class="btn-medium btn-outlined" >
                    Lakukan Kehadiran
                </button>
                @endif
            </div>
            @endif
        </div>

        <form wire:submit="submit_attendance" >
            <x-modal 
                title="Absensi" 
                show="modal.student_attendance" 
                onClose="modal.student_attendance = false"
            >
                <div class="flex flex-col gap-y-4" >
                    <label for="passgrade"  >
                        <span class="block label text-gray-600 text-[12px] mb-1" >Status</span>
                        <select wire:model.live="status" name="attempts" class="text-field" >
                            <option value="" >-- Pilih Status --</option>
                            <option value="Hadir" >Hadir</option>
                            <option value="Sakit" >Sakit</option>
                            <option value="Izin" >Izin</option>
                            <option value="Alpa" >Alpa</option>
                        </select>
                        @error('status')
                        <span class="text-error mt-3 text-sm" >{{ $message }}</span>
                        @enderror
                    </label>
                    <label for="urlname" class="" >
                        <span class="block label text-gray-600 text-[12px] mb-1" >Catatan</span>
                        <input wire:model.live="note" type="text" id="urlname" placeholder="Masukkan Catatan"  class="text-field">
                    </label>
                </div>
                <x-slot:footer>
                    <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                        <x-button
                            class="mr-2"
                            type="submit"
                            @click="modal.student_attendance = false"
                        >
                            Simpan
                        </x-button>
                        <x-button
                            variant="outlined"
                            @click="modal.student_attendance = false"
                        >
                            Batal
                        </x-button>
                    </div>
                </x-slot>
            </x-modal>
        </form>

        <x-toast/>

    </div>

    @script
    <script>
        Alpine.data('pages', () => ({
            modal: {
                student_attendance: false
            },
            toast: {
                show:false
            }
        }))


        Livewire.on('notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })

    </script>
    @endscript

    @endvolt
</x-layouts.app>