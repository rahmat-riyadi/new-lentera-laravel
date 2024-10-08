<?php

use function Livewire\Volt\{state, mount, on};
use App\Livewire\Forms\Activity\AttendanceForm;
use App\Models\{
    Course,
    CourseSection,
    Assignment,
    AssignmentSubmission,
    User,
    Context,
    Role,
    AssignmentFile,
    ResourceFile,
};

state([
    'course', 
    'section', 
    'assignment', 
    'role', 
    'students',
    'submitted_count',
    'need_grading_count',
    'student_submission',
    'student_submission_files',
    'type'
]);

mount(function (Course $course,CourseSection $section, Assignment $assignment){
    $ctx = Context::where('contextlevel', 50)->where('instanceid', $course->id)->first();
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

    $online_text_plugin = $assignment->configs()
        ->where('subtype', 'assignsubmission')
        ->where('plugin', 'onlinetext')
        ->where('name', 'enabled')
        ->where('value', 1)
        ->first();

    $file_plugin = $assignment->configs()
        ->where('subtype', 'assignsubmission')
        ->where('plugin', 'file')
        ->where('name', 'enabled')
        ->where('value', 1)
        ->first();

    if($online_text_plugin){
        $this->type = 'onlinetext';
    } 

    if($file_plugin){
        $this->type = 'file';
    }

    if($this->role != 'student'){

        $role = Role::where('shortname', 'student')->first();

        $studentIds = DB::connection('moodle_mysql')->table('mdl_enrol')
                ->where('mdl_enrol.courseid', $course->id)
                ->where('mdl_enrol.roleid', $role->id)
                ->where('mdl_user_enrolments.userid', '!=',auth()->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                ->pluck('mdl_user_enrolments.userid');

        $this->students = User::query()
        ->whereIn('mdl_user.id', $studentIds)
        ->leftJoin('mdl_assign_submission as s', function($q) use ($assignment) {
            $q->on('s.userid', '=', 'mdl_user.id')
            ->where('s.assignment', $assignment->id);
        })
        ->select(
            'mdl_user.id',
            's.id as assignment_submission_id',
            DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
            'mdl_user.username as nim',
            's.timecreated',
            's.timemodified',
            's.status',
        )
        ->get();

        
        $this->submitted_count = DB::connection('moodle_mysql')
        ->table('mdl_assign_submission as s')
        ->where('s.latest', 1)
        ->where('s.assignment', $assignment->id)
        ->whereNotNull('s.timemodified')
        ->where('s.status', 'submitted')
        ->whereIn('s.userid', $studentIds)
        ->count('s.userid');

        $this->need_grading_count = DB::connection('moodle_mysql')
        ->table('mdl_assign_submission as s')
        ->leftJoin('mdl_assign_grades as g', function($q){
            $q->on('s.assignment', '=', 'g.assignment')
            ->on('s.userid', '=', 'g.userid')
            ->on('s.attemptnumber', '=', 'g.attemptnumber');
        })
        ->whereIn('s.userid', $studentIds)
        ->where('s.latest', 1)
        ->where('s.assignment', $assignment->id)
        ->where('s.status', 'submitted')
        ->whereNotNull('s.timemodified')
        ->where(function ($query) {
            $query->where('s.timemodified', '>=', DB::raw('g.timemodified'))
                ->orWhereNull('g.timemodified')
                ->orWhereNull('g.grade');
        })
        ->count('s.userid');
        
    } else {

        $this->student_submission = AssignmentSubmission::where('userid', auth()->user()->id)
        ->where('assignment', $assignment->id)
        ->first();

        if($this->student_submission){
            if($this->type == 'file'){
                $submission_files = ResourceFile::where('userid', auth()->user()->id)
                ->where('component', 'assignsubmission_file')
                ->where('filearea', 'submission_files')
                ->where('itemid', $this->student_submission->id)
                ->where('filename', '!=', '.')
                ->get();
    
                $this->student_submission_files = $submission_files->map(function($e){
                    $filedir = substr($e->contenthash, 0, 4);
                    $formatted_dir = substr_replace($filedir, '/', 2, 0);
    
                    $ext = explode('.',$e->filename);
                    $ext = $ext[count($ext)-1];
    
                    $e->name = $e->filename;
                    $e->file = "/preview/file/$e->id/$e->filename";
                    $e->size = $e->filesize;
                    $e->itemid = $e->itemid;
                    return $e;
                });
    
            }
        }

    }

    if(session('success')){
        $this->dispatch('notify-delay', 'success', session('success'));
    }

});


$download = function ($id){
    $file = AssignmentFile::find($id);
    return response()->download(public_path('storage/'.$file->path), $file->name);
};

?>

<x-layouts.app>
    @volt
    <div class="h-screen md:h-full overflow-y-auto" >
        <x-activity-subheader 
            path="/course/{{ $course->shortname }}" 
            title="Detail Penugasan"
            :course="$course"
            :section="$section"
        />

        <div class="p-8">
            @if ($role != 'student')
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $assignment->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $assignment->description !!}</p>
                <div class="flex mt-4">
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Tenggat Waktu</td>
                            <td class="text-[#121212] text-sm" >: {{ Carbon\Carbon::parse($assignment->duedate)->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Waktu Tersisa</td>
                            <td class="text-[#121212] text-sm" >: {{ Carbon\Carbon::parse($assignment->duedate)->diffForHumans(['parts' => 2]) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Jenis Pengiriman</td>
                            <td class="text-[#121212] text-sm" >: {{ $type == 'file' ? 'Berkas' : 'Text Daring'  }}</td>
                        </tr>
                    </table>
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Peserta</td>
                            <td class="text-[#121212] text-sm" >: {{ count($students) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Terkumpul</td>
                            <td class="text-[#121212] text-sm" >: {{ $submitted_count }}</td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 37px;" class="text-grey-500 text-sm" >Belum Dinilai</td>
                            <td class="text-[#121212] text-sm" >: {{ $need_grading_count }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="bg-white p-5 mt-6 rounded-xl">
                <table class=" w-full" >
                    <thead class="table-head" >
                        <tr>
                            <td class="" >Mahasiswa</td>
                            <td >Waktu Pengumpulan</td>
                            <td class="w-[170px]" >Status</td>
                            <td class="text-center  w-[150px]" >Total Nilai</td>
                            <td class="w-[150px]" >Aksi</td>
                        </tr>
                    </thead>
                    <tbody class="table-body" >
                        @foreach ($students as $student)
                        <tr>
                            <td>
                                <div class="flex items-center" >
                                    <img src="{{ asset('/assets/images/avatar.webp') }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                    <div>
                                        <p class="mb-1">{{ $student->fullname }}</p>
                                        <span class="text-grey-500 " >{{ $student->nim }}</span>
                                    </div>
                                </div>
                            </td>
                            <td >
                                @if (!empty($student->timemodified))
                                {{ \Carbon\Carbon::parse($student->timemodified)->translatedFormat('d F Y, H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if (is_null($student->timemodified))
                                    <p class="chip empty text-center px-3 text-xs w-fit font-medium rounded-xl">Belum Dikumpulkan</p>
                                @else
                                    @php
                                        $sub_time = \Carbon\Carbon::parse($student->timemodified);
                                        $assign_time = \Carbon\Carbon::parse($assignment->duedate);
                                    @endphp
                                    @if ($sub_time->gt($assign_time))
                                    <p class="chip late text-center px-3 text-xs w-fit font-medium rounded-xl">Terlambat Dikumpulkan</p>
                                    @else
                                    <p class="chip attend text-center px-3 text-xs w-fit font-medium rounded-xl">Dikumpulkan</p>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center" >
                                {{ !is_null($student->grade) ? number_format($student->grade, 2, ',') : '0,00' }}
                            </td>
                            <td >
                                <a 
                                    class="btn btn-outlined" 
                                    href='{{ is_null($student->assignment_submission_id) ? "javascript:;" : "/teacher/assignment/$assignment->id/grade/$student->assignment_submission_id" }}'
                                >
                                    Nilai
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>    
            @else
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $assignment->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $assignment->description !!}</p>
                {{-- @if (count($assignment->files) > 0)
                <div class="flex flex-col mt-4" >
                    @foreach ($assignment->files ?? [] as $i => $item)
                    <div  class="flex items-center px-4 py-2 bg-grey-100 rounded-lg mb-3" >
                        <img class="w-7 mr-4" src="{{ asset('assets/icons/berkas_lg.svg') }}" >
                        <div>
                            <p class="font-semibold text-sm mb-[2px]" >
                                <a target="_blank" class="hover:underline" href="{{ url('storage/'.$item->path) }}" >{{ $item->name }}</a>
                            </p>
                            <p class="text-xs text-grey-500"  >{{ $item->size }}</p>
                        </div>
                        <span class="ml-auto text-sm underline text-blue-500" wire:click="download('{{ $item->id }}')" >
                            download
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif --}}
                <table class="w-full font-normal md:font-medium mt-4" >
                    <tr>
                        <td style="height: 37px;" class="text-grey-500 text-sm  md:w-[210px]" >Batas Waktu</td>
                        <td class="text-[#121212] text-sm" > <span class="mr-1" >:</span> {{ Carbon\Carbon::parse($assignment->duedate)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="height: 37px;" class="text-grey-500 text-sm  md:w-[210px]" >Waktu Tersisa</td>
                        <td class="text-[#121212] text-sm" > 
                            <span class="mr-1" >:</span>
                            @php
                                $is_late = false;

                                if($student_submission) {
                                    if(Carbon\Carbon::parse($assignment->duedate)->lessThan(Carbon\Carbon::parse($student_submission->timemodified))) {
                                        $is_late = true;
                                    }
                                }


                            @endphp
                            @if (!$student_submission)
                                {{ Carbon\Carbon::parse($assignment->duedate)->diff()->format('%H Jam %i Menit') }}
                            @else
                                @if($is_late)
                                <span class="text-error" >
                                    Tugas Dikumpulkan {{ Carbon\Carbon::parse($assignment->duedate)->diffInHours(Carbon\Carbon::parse($student_submission->timemodified)) }} Lebih Lambat
                                </span>
                                @else
                                {{ Carbon\Carbon::parse($assignment->timemodified) }}
                                @endif
                            @endif  
                        </td>
                    </tr>
                    <tr>
                        <td style="height: 37px;" class="text-grey-500 text-sm  md:w-[210px]" >Status</td>
                        <td class="text-[#121212] text-sm" >
                            <span class="mr-1" >:</span>
                            @if (!empty($student_submission) && $student_submission->status == 'submitted')
                            <span class="chip px-3 py-1 text-xs rounded-md attend" >Dikumpulkan</span>
                            @else
                            <span class="chip empty" >-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style=" height: 37px;" class="text-grey-500 text-sm md:w-[210px]" >Penilaian</td>
                        <td class="text-[#121212] text-sm" >
                            <span class="mr-1" >:</span>
                            @if (!empty($student_submission->grade))
                            <span class="chip px-2 py-1  rounded-md attend" > {{ $student_submission->grade }} </span>
                            @else
                            <span class="chip empty" >-</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <div class="h-4" ></div>
                @if (empty($student_submission) || $student_submission->status == 'new')
                <a wire:navigate.hover href="/student/assignment/{{ $assignment->id }}/submit" class="btn btn-outlined text-center inline-block w-full md:w-fit">
                    {{ !empty($student_submission) && $student_submission->status == 'submitted' ? 'Ubah' : 'Ajukan' }} Penugasan
                </a>
                @endif
            </div>
            @if (!empty($student_submission) && $student_submission->status == 'submitted')
                <div class="bg-white p-5 rounded-xl mt-4" >
                    <h3 class="font-semibold mb-2" >
                        {{ $type  }}
                    </h3>
                    @if ($type == 'file')
                    <div class="flex flex-col mt-4" >
                        @foreach ($student_submission_files as $file)
                        <div  class="flex items-center px-4 py-2 rounded-lg mb-3 bg-grey-100" >
                            <img class="w-7 mr-4" src="{{ asset('assets/icons/berkas_lg.svg') }}" >
                            <p class="font-semibold text-sm mb-[2px]" >
                                <a target="_blank" class="hover:underline" href="{{ $file->file }}" >{{ $file->name }}</a>
                            </p>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div  class="flex items-center px-4 py-2 pl-0 rounded-lg mb-3" >
                        <img class="w-7 mr-4" src="{{ asset('assets/icons/url.svg') }}" >
                        <p class="font-semibold text-sm mb-[2px]" >
                            <a target="_blank" class="hover:underline underline" href="{{ $student_submission->url->url }}" >{{ $student_submission->url->url }}</a>
                        </p>
                    </div>
                    @endif
                    @if (empty($student_submission->grade))
                    <a wire:navigate.hover href="/student/assignment/{{ $assignment->id }}/submit" class="btn btn-outlined text-center inline-block w-full md:w-fit mt-3">
                        Edit Penugasan
                    </a>
                    @endif
                </div>
            @endif    
            @endif
        </div>

        <x-toast/>

    </div>

    @script
    <script>

        Livewire.on('notify-delay', ([ type, message ]) => {

            setTimeout(() => {
                Alpine.store('toast').show = true
                Alpine.store('toast').type = type
                Alpine.store('toast').message = message
                setTimeout(() => {
                    Alpine.store('toast').show = false
                }, 2000);
            }, 100)

        })
        
    </script>
    @endscript

    @endvolt
</x-layouts.app>