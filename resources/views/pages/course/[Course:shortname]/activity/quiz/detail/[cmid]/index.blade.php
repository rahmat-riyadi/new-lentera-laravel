<?php

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};    
use App\Models\CourseModule;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\AssignPluginConfig;
use App\Models\Role;
use App\Models\Enrol;
use App\Models\AssignGrade;
use App\Models\AssignSubmission;
use Illuminate\Support\Facades\DB;

middleware(['auth']);
name('courseModule');
state([
    'courseModule',
    'quiz',
    'assignType',
    'participantCount',
    'submitted',
    'needGrading',
    'studentSubmissionList',
    'course'
]);

mount(function ($cmid, Course $course){
    $this->courseModule = CourseModule::find($cmid);
    $this->quiz = Quiz::find($this->courseModule->instance);
    $studentRole = Role::where('shortname', 'student')->first();
    $this->getParticipantCount($this->quiz->course,$studentRole->id);
    // $this->getSubmittedCount($this->quiz->course, $studentRole->id, $this->assign->id, $this->assign->duedate);
    // Log::debug($this->assign->submission);

});

$getParticipantCount = function ($courseid, $roleid){

    $data = DB::table('mdl_enrol')
                ->where('mdl_enrol.courseid', $courseid)
                ->where('mdl_enrol.roleid', $roleid)
                ->where('mdl_user_enrolments.userid', '!=',auth()->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                ->count();

    $this->participantCount = $data;

};

$getSubmittedCount = function ($courseid, $roleid, $assignid, $duedate){

    $students = DB::table('mdl_enrol')
                ->where('mdl_enrol.courseid', $courseid)
                ->where('mdl_enrol.roleid', $roleid)
                ->where('mdl_user_enrolments.userid', '!=', auth()->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                ->join('mdl_user', 'mdl_user.id', '=', 'mdl_user_enrolments.userid')
                ->select(
                    'mdl_user.id',
                    DB::raw("CONCAT(mdl_user.firstname, ' ', mdl_user.lastname) as fullname"),
                    'mdl_user.username as nim'
                )
                ->get();

    $newStudentsData = [];

    foreach ($students as $student) {

        $studentObject = new stdClass();

        $studentObject->fullname = $student->fullname;
        $studentObject->nim = $student->nim;

        $submissionData = AssignSubmission::where('userid', $student->id)
                                ->where('assignment', $assignid)
                                ->first();

        if(is_null($submissionData)){
            $studentObject->status = null;
            $studentObject->submissiontime = null;
            $studentObject->is_late = null;
            $studentObject->submission_id = null;
        } else {
            $studentObject->submission_id = $submissionData->id;
            $studentObject->status = $submissionData->status;
            $submissiontime = Carbon\Carbon::parse($submissionData->timecreated);
            $studentObject->submissiontime = $submissiontime->translatedFormat('d F Y, H:i');
            $studentObject->is_late = $submissiontime->gt(Carbon\Carbon::parse($duedate));
        }

        $submissionGradeData = AssignGrade::where('userid', $student->id)
                                ->where('assignment', $assignid)
                                ->first(
                                    'grade'
                                );

        if(is_null($submissionGradeData)){
            $studentObject->grade = null;
        } else {
            $studentObject->grade = $submissionGradeData->grade;
        }

        
        $newStudentsData[] = $studentObject;

    }

    $this->studentSubmissionList = $newStudentsData;

    $this->submitted = Arr::where($newStudentsData, function ($val){
        return !is_null($val->submissiontime);
    });

    $this->needGrading = Arr::where($newStudentsData, function ($val){
        return !is_null($val->submissiontime) && is_null($val->grade);
    });

    Log::debug($this->needGrading);

};

?>

<x-layouts.app>
    @volt
    <div class="h-full overflow-y-auto">
        <div class=" bg-white course-page-header px-8 py-8 font-main flex flex-col" >
            <x-back-button path="/" />
            <p class="text-sm text-[#656A7B] font-[400] flex items-center my-5" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> Pemgrograman Web 1 - Kelas A <span class="mx-2 text-[9px]" > >> </span>  <span class="text-[#121212]" >Detail Penugasan - {{ $courseModule->sectionDetail->name }}</span></p>
            <div class="flex justify-between items-center">
                <h1 class="text-[#121212] text-xl font-semibold" >Detail Penugasan - {{ $courseModule->sectionDetail->name }}</h1>
                <button class="btn btn-primary" >Edit Quiz</button>
            </div>
        </div>

        <div class="p-8">
            <div class="bg-white p-5 rounded-xl">
                <h3 class="font-semibold text-lg mb-2" >{{ $quiz->name }}</h3>
                <p class="text-grey-700 text-sm" > {!! $quiz->intro !!}</p>
                <div class="columns-4 mt-4 font-medium text-sm" >
                    <p class="text-grey-500" >Tenggat Waktu</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ Carbon\Carbon::parse($quiz->timeclose)->translatedFormat('d F Y, H:i') }}</p>
                    <p class="text-grey-500" >Peserta</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ $participantCount }}</p>
                </div>
                <div class="columns-4 mt-3 font-medium text-sm" >
                    <p class="text-grey-500" >Waktu Pengerjaan</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ Carbon\Carbon::parse($quiz->timeclose)->diffForHumans() }}</p>
                    <p class="text-grey-500" >Telah Mengerjakan</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ count($submitted ?? []) }}</p>
                </div>
                <div class="columns-4 mt-3 font-medium text-sm">
                    <p class="text-grey-500" >Percobaan Menjawab</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ $quiz->attempts }}</p>
                </div>
                {{-- <div class="columns-4 mt-3 font-medium text-sm" >
                    <p class="text-grey-500" >Jenis Pengiriman</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ $this->assignType }}</p>
                    <p class="text-grey-500" >Belum dinilai</p>
                    <p class="text-[#121212]" ><span class="mr-2" >:</span> {{ count($needGrading) }}</p>
                </div> --}}
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
                        @foreach ($studentSubmissionList ?? [] as $student)
                        <tr>
                            <td>
                                <div class="flex items-center" >
                                    <img src="/images/avatar.jpg" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                    <div>
                                        <p class="mb-1">{{ $student->fullname }}</p>
                                        <span class="text-grey-500 " >{{ $student->nim }}</span>
                                    </div>
                                </div>
                            </td>
                            <td >
                                {{ $student->submissiontime ?? '-' }}
                            </td>
                            <td>
                                <p class="chip {{ is_null($student->is_late) ? 'empty' : (($student->is_late) ? 'late' : 'attend' ) }} text-center px-3 text-xs w-fit font-medium rounded-xl">{{ is_null($student->is_late) ? 'Belum' : (!$student->is_late ? 'Telah' : 'Terlambat')}} Dikumpulkan</p>
                            </td>
                            <td class="text-center" >
                                {{ !is_null($student->grade) ? number_format($student->grade, 2, ',') : '0,00' }}
                            </td>
                            <td >
                                <a 
                                    @if (is_null($student->submission_id))
                                    href="javascript:;" 
                                    @else
                                    wire:navigate 
                                    href="/course/{{ $course->shortname }}/activity/assignment/detail/{{ $courseModule->id }}/assessment/{{ $student->submission_id }}" 
                                    @endif
                                    class="btn btn-outlined" 
                                >
                                    Nilai
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>


    </div>
    @endvolt
</x-layouts.app>