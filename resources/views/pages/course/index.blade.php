<?php

use function Livewire\Volt\{state, mount, on, updated};
use App\Helpers\GlobalHelper;
use App\Exports\GradeExport;
use App\Helpers\CourseHelper;
use App\Models\{
    Module,
    CourseModule,
    Course,
    CourseSection,
    Url,
    Resource,
    Assign,
    Context,
    Role,
    User,
    Quiz,
    Assignment,
    StudentQuiz,
    AssignmentSubmission,
    Attendance,
    StudentAttendance,
};

state([
    'sections', 
    'course', 
    'topic', 
    'role', 
    'participants', 
    'teacher',
    'grading',
    'grading_table_type',
    'enrolled_course',
    'imported_course_sections',
    'selected_imported_contents'
]);

mount(function(Course $course){
    $this->grading_table_type = 'all';
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
    $this->topic = new stdClass();
    $this->get_sections($course);
    $this->course = $course;

    $context = Context::where('instanceid', $course->id)
    ->where('contextlevel', 50)
    ->first();

    $participantsData = DB::connection('moodle_mysql')
    ->table('mdl_role_assignments as ra')
    ->where('contextid', $context->id)
    ->join('mdl_user as u', 'u.id', '=', 'ra.userid')
    ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
    ->select(
        'u.id',
        DB::raw("CONCAT(u.firstname,' ',u.lastname) as fullname"),
        'u.username as nim',
        'u.email as email',
        'r.shortname as role',
        DB::raw("
            (
                SELECT
                f.contenthash
                FROM
                mdl_files f
                INNER JOIN mdl_context c ON f.contextid = c.id
                WHERE
                f.component = 'user'
                AND f.filearea = 'icon'
                AND c.contextlevel = 30
                AND c.instanceid = u.id
                ORDER BY f.timecreated DESC
                LIMIT 1
            ) AS ctx
        ")
    )->get();

    $this->teacher = $participantsData->first(fn($e) => $e->role == 'editingteacher');
    $this->participants = $participantsData->filter(fn($e) => $e->role != 'editingteacher')->toArray();

    if($this->role != 'student'){
        $this->set_grading_data();
        $time = time();
        $this->enrolled_course = Course::
        whereIn('mdl_course.id', function($q) use ($time){
            $q->select('e.courseid')
            ->from('mdl_enrol as e')
            ->join('mdl_user_enrolments as ue', function ($join) {
                $join->on('ue.enrolid', '=', 'e.id')
                    ->where('ue.userid', '=', auth()->user()->id);
            })
            ->join('mdl_course as c', 'c.id', '=', 'e.courseid')
            ->where('ue.status', '=', '0')
            ->where('e.status', '=', '0')
            ->where('ue.timestart', '<=', $time)
            ->where(function ($query) use ($time) {
                $query->where('ue.timeend', '=', 0)
                        ->orWhere('ue.timeend', '>', $time);
            });
        })
        ->where('id', '!=', $this->course->id)
        ->select(
            'id',
            'shortname',
            'fullname',
        )
        ->get();
    }

    if(session()->has('success')){
        $this->dispatch('notify-delay', 'success', session('success'));
    }

});

$set_grading_data = function ($type = 'all'){
    $studentIds = collect($this->participants)->pluck('id');

    $gradesStudent = User::query()
    ->whereIn('mdl_user.id', $studentIds)
    ->select(
        'mdl_user.id',
        DB::raw("CONCAT(mdl_user.firstname,' ',mdl_user.lastname) as fullname"),
        'mdl_user.username as nim',
    )
    ->get();

    if($type == 'all' || $type == 'quiz'){
        $selectedQuiz = Quiz::where('course_id', $this->course->id)->orderBy('created_at')->get();
    }

    if($type == 'all' || $type == 'assignment'){
        $selectedAssignment = Assignment::where('course', $this->course->id)->orderBy('timemodified')->get();
    }

    if($type == 'all' || $type == 'attendance'){
        $selectedAttendance = Attendance::where('course', $this->course->id)->orderBy('timemodified')->get();
    }
    
    foreach($gradesStudent as $student){

        if($type == 'all' || $type == 'attendance'){
            $student->attendance_grades = new stdClass();
            foreach($selectedAttendance->pluck('id') as $i => $attId){
                $attendance = StudentAttendance::where('attendance_id', $attId)
                ->where('student_id', $student->id)
                ->select(
                    'id',
                    'status'
                )
                ->first();
    
                $student->attendance_grades->{"attendance_$i"} = new stdClass();
                $student->attendance_grades->{"attendance_$i"}->title = $selectedAttendance->first(fn($e) => $e->id == $attId)->name ?? null;
                $student->attendance_grades->{"attendance_$i"}->grade = $attendance->status ?? null;
    
            }
        }

        if($type == 'all' || $type == 'quiz'){
            $student->quiz_grades = new stdClass();
            foreach($selectedQuiz->pluck('id') as $i => $qid){
                $quiz = StudentQuiz::where('quiz_id', $qid)
                ->where('student_id', $student->id)
                ->leftJoin('student_quiz_answers as sqa', 'sqa.student_quiz_id', '=', 'student_quizzes.id')
                ->select(
                    'student_quizzes.quiz_id',
                    'sqa.grade'
                )
                ->first();
    
                $student->quiz_grades->{"quiz_$i"} = new stdClass();
                $student->quiz_grades->{"quiz_$i"}->title = $selectedQuiz->first(fn($e) => $e->id == $qid)->name ?? null;
                $student->quiz_grades->{"quiz_$i"}->grade = $quiz->grade ?? 0.00;
    
            }
        }

        if($type == 'all' || $type == 'assignment'){
            $student->assignment_grades = new stdClass();
            foreach($selectedAssignment->pluck('id') as $i => $aid){
    
                $assignment = AssignmentSubmission::where('assignment_id', $aid)
                ->where('student_id', $student->id)
                ->select(
                    'grade'
                )
                ->first();
    
                $student->assignment_grades->{"assignment_$i"} = new stdClass();
                $student->assignment_grades->{"assignment_$i"}->title = $selectedAssignment->first(fn($e) => $e->id == $aid)->name ?? null;
                $student->assignment_grades->{"assignment_$i"}->grade = $assignment->grade ?? 0.00;
    
            }
        }
    }

    $metaData = [
        'quiz' => ($type == 'all' || $type == 'quiz') ? $selectedQuiz->pluck('name') : [],
        'assignment' => ($type == 'all' || $type == 'assignment') ? $selectedAssignment->pluck('name') : [],
        'attendance' => ($type == 'all' || $type == 'attendance') ? $selectedAttendance->pluck('name') : [],
        'type' => $type
    ];

    $this->dispatch('init-table', $gradesStudent, $metaData);

    if(session()->has('success')){
        $this->dispatch('notify', 'success', 'Berhasil mengimpor kelas');
    }

};

$export = function (){
    $selectedQuiz = Quiz::where('course_id', $this->course->id)->orderBy('created_at')->get();

    if($this->grading_table_type == 'all' || $this->grading_table_type == 'assignment'){
        $selectedAssignment = Assignment::where('course_id', $this->course->id)->orderBy('created_at')->get();
    }

    if($this->grading_table_type == 'all' || $this->grading_table_type == 'quiz'){
        $selectedQuiz = Quiz::where('course_id', $this->course->id)->orderBy('created_at')->get();
    }

    if($this->grading_table_type == 'all' || $this->grading_table_type == 'attendance'){
        $selectedAttendance = Attendance::where('course_id', $this->course->id)->orderBy('created_at')->get();
    }

    switch ($this->grading_table_type) {
        case 'all':
            $name = $this->course->fullname . " - Nilai Tugas Quiz Kehadiran.xlsx";
            break;
        case 'assignment':
            $name = $this->course->fullname . " - Nilai Tugas.xlsx";
            break;
        case 'quiz':
            $name = $this->course->fullname . " - Nilai Quiz.xlsx";
            break;
        case 'attendance':
            $name = $this->course->fullname . " - Rekap Kehadiran.xlsx";
            break;
    }

    return (new GradeExport($this->course, $selectedQuiz ?? [], $selectedAssignment ?? [], $selectedAttendance ?? [],$this->grading_table_type))->download($name);
};  

$get_sections = function ($course){
    $sections = [];

    $courseSections = CourseSection::where('course', $course->id)->get();

    foreach($courseSections as $cs){

        $section = new stdClass();

        $section->id = $cs->id;
        $section->name = $cs->name;
        $section->section = $cs->section;
        $section->modules = [];

        if(!empty($cs->sequence)){

            $cmids = explode(',', $cs->sequence);

            $courseModules = CourseModule::whereIn('id', $cmids)
            ->where('deletioninprogress', 0)
            ->where('course', $course->id)
            ->get();

            foreach($courseModules as $cm){

                $module = new stdClass();

                $module->id = $cm->id;
                $module->instance = $cm->instance;
                $module->module = $cm->module;

                $selectedModule = Module::find($cm->module);

                switch ($selectedModule->name) {
                    case 'url':
                        $mod_table = 'mdl_url';
                        break;
                    case 'resource':
                        $mod_table = 'mdl_resource';
                        break;
                    case 'attendance':
                        $mod_table = 'mdl_attendance';
                        break;
                    case 'assign':
                        $mod_table = 'mdl_assign';
                        break;
                    case 'quiz':
                        $mod_table = 'quizzes';
                        break;
                }

                if(!empty($mod_table)){
                    $instance = DB::connection('moodle_mysql')->table($mod_table)
                    ->where('id', $cm->instance)
                    ->first();
                }


                $module->name = $instance->name ?? '';
                $module->description = $instance->intro ?? '';
                $module->modname = $selectedModule->name;

                if($selectedModule->name == 'url'){
                    $module->url = $instance->externalurl ?? '';   
                }

                if($selectedModule->name == 'assign'){
                    
                }

                if($selectedModule->name == 'resource'){

                    $file_ctx = DB::connection('moodle_mysql')->table('mdl_context')
                    ->where('instanceid', $cm->id)
                    ->where('contextlevel', 70)
                    ->first('id');

                    $files = DB::connection('moodle_mysql')->table('mdl_files')
                    ->where('contextid', $file_ctx->id)
                    ->where('component', 'mod_resource')
                    ->whereNotNull('mimetype')
                    ->whereNotNull('source')
                    ->orderBy('id')
                    ->get();

                    $module->file = $files->map(function($e){

                        $filedir = substr($e->contenthash, 0, 4);
                        $formatted_dir = substr_replace($filedir, '/', 2, 0);

                        $ext = explode('.',$e->filename);
                        $ext = $ext[count($ext)-1];

                        $e->name = $e->filename;
                        $e->file = "/preview/file/$e->id/$e->filename";
                        return $e;
                    });   
                }

                $section->modules[] = $module;
            }
        }

        $sections[] = $section;
    }

    $this->sections = $sections;
    
};

$change_section_title = function (){
    CourseSection::where('id',$this->topic->id)->update([
        'name' => $this->topic->text,
        'timemodified' => time()
    ]);
    GlobalHelper::rebuildCourseCache($this->course->id);
    $this->get_sections($this->course);
    $this->dispatch('title-changed');
};

$add_section = function (){
    $currSection = $this->course->section->max('section');
    $this->course->section()->create([
        'section' => $currSection+1,
        'summaryformat' => 1,
        'name' => "Topic ".$currSection+1,
        'sequence' => ' ',
        'summary' => ' ',
        'timemodified' => time()
    ]);
    GlobalHelper::rebuildCourseCache($this->course->id);
    $this->get_sections($this->course);
    $this->dispatch('notify', 'success', 'Berhasil menambah topik');
};

$delete_section = function ($id){
    $cs = CourseSection::find($id);
    if(!empty($cs->sequence)){
        $cmids = explode(',', $cs->sequence);
        foreach($cmids as $cm){
            if(is_numeric($cm)){
                $courseModule = CourseModule::find($cm);
                $selectedModule = Module::find($courseModule->module);
                if($selectedModule){
                    switch ($selectedModule->name) {
                        case 'url':
                            $mod_table = 'url';
                            break;
                        case 'resource':
                            $mod_table = 'resource';
                            break;
                        case 'attendances':
                            $mod_table = 'attendances';
                            break;
                        case 'assign':
                            $mod_table = 'assignments';
                            break;
                        case 'quiz':
                            $mod_table = 'quizzes';
                            break;
                    }
                    if(!empty($mod_table)){
                        $instance = DB::table($mod_table)
                        ->where('id', $courseModule->instance)
                        ->delete();
                    }
                }
            }
        }

    }
    CourseSection::where('id', $id)->delete();
    GlobalHelper::rebuildCourseCache($this->course->id);
    $this->get_sections($this->course);
    $this->dispatch('section-deleted-notify', 'success', 'Berhasil menghapus topik', $id);
};

$delete_activity = function ($id){

    DB::beginTransaction();

    try {
        $cm = CourseModule::find($id);
        $selectedModule = Module::find($cm->module);
        switch ($selectedModule->name) {
            case 'url':
                $mod_table = 'url';
                break;
            case 'resource':
                $mod_table = 'resource';
                break;
            case 'attendances':
                $mod_table = 'attendances';
                break;
            case 'assign':
                $mod_table = 'assignments';
                break;
            case 'quiz':
                $mod_table = 'quizzes';
                break;
        }
        DB::table($mod_table)
        ->where('id', $cm->instance)
        ->delete();
        $cm->update(['deletioninprogress' => 1]);
        GlobalHelper::rebuildCourseCache($this->course->id);
        $this->get_sections($this->course);
        DB::commit();
        $this->dispatch('notify', 'success', 'Berhasil menghapus aktivitas');
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::info($th->getMessage());
        $this->dispatch('notify', 'error', 'Terjadi Kesalahan');
    }

};

$get_imported_course_info = function ($id){
    $this->selected_imported_contents = [];
    $sections = [];

    $courseSections = CourseSection::where('course', $id)->get();

    foreach($courseSections as $cs){

        $section = new stdClass();

        $section->id = $cs->id;
        $section->name = $cs->name;
        $section->section = $cs->section;
        $section->modules = [];

        $this->selected_imported_contents[$cs->id] = [];

        if(!empty($cs->sequence)){

            $cmids = explode(',', $cs->sequence);

            $this->selected_imported_contents[$cs->id] = $cmids;

            $courseModules = CourseModule::whereIn('id', $cmids)
            ->where('deletioninprogress', 0)
            ->where('course', $id)
            ->get();

            foreach($courseModules as $cm){

                $module = new stdClass();

                $module->id = $cm->id;
                $module->instance = $cm->instance;
                $module->module = $cm->module;

                $selectedModule = Module::find($cm->module);

                switch ($selectedModule->name) {
                    case 'url':
                        $mod_table = 'url';
                        break;
                    case 'resource':
                        $mod_table = 'resource';
                        break;
                    case 'attendances':
                        $mod_table = 'attendances';
                        break;
                    case 'assign':
                        $mod_table = 'assignments';
                        break;
                    case 'quiz':
                        $mod_table = 'quizzes';
                        break;
                }

                if($selectedModule->name == 'url'){
                    $fields = ['id', 'name', 'description', 'url'];
                } else {
                    $fields = ['id', 'name', 'description'];
                }

                $instance = DB::table($mod_table)
                ->where('id', $cm->instance)
                ->first($fields);

                $module->name = $instance->name ?? '';
                $module->description = $instance->description ?? '';
                $module->modname = $selectedModule->name;

                if($selectedModule->name == 'url'){
                    $module->url = $instance->url;   
                }

                if($selectedModule->name == 'resource'){
                    $file = DB::table('resource_files')->where('resource_id', $instance->id)->first('file');
                    $module->file = url('storage/'.$file->file);   
                }

                $section->modules[] = $module;
            }
        }

        
        $sections[] = $section;
    }

    $this->imported_course_sections = $sections;

    $this->selected_imported_contents = collect($this->selected_imported_contents);

    Log::info($this->selected_imported_contents->all());

};

$handle_checked_imported_section = function ($id){
    if($this->selected_imported_contents->contains(fn($val, $key) => $key == $id)){
        $this->selected_imported_contents = $this->selected_imported_contents->filter(fn($val, $key) => $key != $id);
    } else {
        $this->selected_imported_contents->put($id, []);
    }

    Log::info($this->selected_imported_contents->all());

};

$handle_checked_imported_section_module = function ($section_id, $mod_id){
    Log::info($section_id);
    Log::info($mod_id);
    if($this->selected_imported_contents->contains(fn($val, $key) => $key == $section_id)){
        if(in_array($mod_id,$this->selected_imported_contents->get($section_id))){
            $mod_ids = array_filter($this->selected_imported_contents->get($section_id), function($val) use ($mod_id){
                return $val != $mod_id;
            });
            $this->selected_imported_contents = $this->selected_imported_contents->replace([$section_id => $mod_ids]);
        } else {
            $this->selected_imported_contents = $this->selected_imported_contents->replace([$section_id => [ ...$this->selected_imported_contents->get($section_id), $mod_id ]]);
        }
    } else {
        Log::info('not contains');
        $this->selected_imported_contents = $this->selected_imported_contents->replace([$section_id => [$mod_id]]);
    }
    Log::info($this->selected_imported_contents->all());
};  

$import_class = function (){

    DB::beginTransaction();

    try {

        CourseModule::where('course', $this->course->id)->delete();
        CourseSection::where('course', $this->course->id)->delete();

        foreach($this->selected_imported_contents as $key => $section){

            $oldSection = CourseSection::find($key);

            $this->course->section()->create([
                ...collect($oldSection)->except(['sequence', 'course', 'id']),
                'timemodified' => time()
            ]);

            foreach ($section as $module) {
                if(is_numeric($module)){
                    
                    $oldCourseModule = CourseModule::find($section)->first();

                    Log::info($oldCourseModule);
                    
                    if ($oldCourseModule) {
                        $selectedModule = Module::find($oldCourseModule->module);
                        Log::info($selectedModule);
                        switch ($selectedModule->name) {
                            case 'url':
                                $instance = Url::find($oldCourseModule->instance);
                                $newInstance = $this->course->url()->create(
                                    ...collect($instance)->except(['id', 'created_at', 'update_at', 'course_id'])
                                );
                                break;
                            case 'resource':
                                $instance = Resource::find($oldCourseModule->instance);
                                $newInstance = $this->course->resource()->create(
                                    ...collect($instance)->except(['id', 'created_at', 'update_at', 'course_id'])
                                );
                                foreach($instance->files as $file){
                                    $newInstance->files()->create(collect($file)->except(['id', 'created_at', 'update_at']));
                                }
                                break;
                            case 'attendance':
                                $instance = Attendance::find($oldCourseModule->instance);
                                $newInstance = $this->course->attendance()->create([
                                    'name' => $instance->name,
                                    'description' => $instance->description,
                                    'date' => $instance->date,
                                    'starttime' => $instance->starttime,
                                    'endtime' => $instance->endtime,
                                    'filled_by' => $instance->filled_by,
                                    'is_repeat' => $instance->is_repeat,
                                    'repeat_attempt' => $instance->repeat_attempt,
                                ]);
                                $role = Role::where('shortname', 'student')->first();
                                $participantsData = DB::connection('moodle_mysql')
                                ->table('mdl_enrol')
                                ->where('mdl_enrol.courseid', '=', $this->course->id)
                                ->where('mdl_enrol.roleid', '=', $role->id)
                                ->where('mdl_user_enrolments.userid', '!=', auth()->user()->id)
                                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
                                ->join('mdl_user', 'mdl_user.id', 'mdl_user_enrolments.userid')
                                ->select('mdl_user.id')->get();
                                $participantsData = $participantsData->map(function($val){
                                    return [
                                        'student_id' => $val->id,
                                    ];
                                });
                                $newInstance->students()->createMany($participantsData);
                                break;
                            case 'assign':
                                $instance = Assignment::find($oldCourseModule->instance);
                                $newInstance = $this->course->assignment()->create([
                                    'name' => $instance->name,
                                    'description' => $instance->description,
                                    'due_date' => $instance->due_date,
                                    'start_date' => $instance->start_date,
                                    'grade' => $instance->grade,
                                    'activity_remember' => $instance->activity_remember,
                                ]);
                                foreach ($instance->configs as $config) {
                                    $newInstance->configs()->create([
                                       'name' => $config->name,
                                       'value' => $config->value,
                                    ]);
                                }
                                break;
                            case 'quiz':
                                $instance = Quiz::find($oldCourseModule->instance);
                                $newInstance = $this->course->quiz()->create(
                                    collect($instance)->except(['id', 'course_id', 'created_at', 'updated_at'])
                                );
                                $role = Role::where('shortname', 'student')->first();
                                $participantsData = DB::connection('moodle_mysql')
                                ->table('mdl_enrol')
                                ->where('mdl_enrol.courseid', '=', $this->course->id)
                                ->where('mdl_enrol.roleid', '=', $role->id)
                                ->where('mdl_user_enrolments.userid', '!=', auth()->user()->id)
                                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', 'mdl_enrol.id')
                                ->join('mdl_user', 'mdl_user.id', 'mdl_user_enrolments.userid')
                                ->select('mdl_user.id')->get();
    
                                foreach($participantsData as $participant){
                                    StudentQuiz::updateOrCreate(
                                        [
                                            'student_id' => $participant->id,
                                            'quiz_id' => $newInstance->id
                                        ],
                                        [
                                            'student_id' => $participant->id,
                                            'quiz_id' => $newInstance->id
                                        ],
                                    );   
                                }
                                break;
                        }
                        $cm = CourseHelper::addCourseModule($this->course->id, $selectedModule->id, $newInstance->id);
                        CourseHelper::addContext($cm->id, $this->course->id);
                        CourseHelper::addCourseModuleToSection($this->course->id, $cm->id, $oldSection->section);
                    }


                } else {
                    Log::info('not nomor');
                }
            }
        }
        DB::commit();
        session()->flash('success', 'Kelas berhasil diimpor');
        $this->redirect('/course/'.$this->course->shortname);
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::info($th->getMessage());
    }

};

on(['delete-section' => 'delete_section']);

on(['delete-module' => 'delete_activity']);

updated(['grading_table_type' => function($e){
    $this->set_grading_data($e);
}]);

?>

<x-layouts.app>
    @volt
    <div x-data="pages({{ $course }})" class="overflow-y-auto h-full pb-3" >
        <div class=" bg-white course-page-header px-8 pt-8 font-main flex flex-col" >
            <p class="text-sm text-[#656A7B] font-[400] flex items-center" >Matakuliah <span class="mx-2 text-[9px]" > >> </span> <span class="text-[#121212]" > Pemgrograman Web 1 - Kelas A</span></p>
            <h1 class="text-[#121212] text-2xl font-semibold mt-8" >{{ $course->fullname }} - Kelas A</h1>
            <div class="flex items-center mt-3" >
                <p class="text-lg" >Teknik Informatika</p>
                @if ($role != 'student')
                <x-button 
                    class="ml-auto"
                    wire:click="add_section"
                >
                    <svg class="w-[16.5px] fill-white mr-2 " width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.5 7.5H10.5V1.5C10.5 1.10218 10.342 0.720645 10.0607 0.43934C9.77936 0.158036 9.39782 0 9 0C8.60218 0 8.22064 0.158036 7.93934 0.43934C7.65804 0.720645 7.5 1.10218 7.5 1.5V7.5H1.5C1.10218 7.5 0.720645 7.65804 0.43934 7.93934C0.158036 8.22064 0 8.60218 0 9C0 9.39782 0.158036 9.77936 0.43934 10.0607C0.720645 10.342 1.10218 10.5 1.5 10.5H7.5V16.5C7.5 16.8978 7.65804 17.2794 7.93934 17.5607C8.22064 17.842 8.60218 18 9 18C9.39782 18 9.77936 17.842 10.0607 17.5607C10.342 17.2794 10.5 16.8978 10.5 16.5V10.5H16.5C16.8978 10.5 17.2794 10.342 17.5607 10.0607C17.842 9.77936 18 9.39782 18 9C18 8.60218 17.842 8.22064 17.5607 7.93934C17.2794 7.65804 16.8978 7.5 16.5 7.5Z"/>
                    </svg>
                    <span>Tambah Pertemuan</span>
                </x-button>
                @endif
                {{-- <button class="btn-icon-light ml-3 hidden md:flex" >
                    <VerticalMoreSvg/>
                </button> --}}
            </div>
            @if ($role != 'student')
            <div class="flex mt-4 gap-x-6" >
                <button  @click="tab = 'proggress'" :class=" tab == 'proggress' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all " >
                    <template x-if="tab == 'proggress'" >
                        <x-icons.chartbar  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'proggress'" >
                        <x-icons.chartbar  class=" fill-grey-400 w-5 transition-all" />
                    </template>
                    <p :class="tab == 'proggress' ? 'text-black' : 'text-grey-400' " class="font-medium  ml-2 text-sm transition-all" >Progres</p>
                </button>
                <button @click="tab = 'value'" :class=" tab == 'value' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all" >
                    <template x-if="tab == 'value'" >
                        <x-icons.coin  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'value'" >
                        <x-icons.coin class="fill-grey-400 transition-all w-5 " />
                    </template>
                    <p :class="tab == 'value' ? 'text-black' : 'text-grey-400' " class="font-medium  transition-all ml-2 text-sm " >Nilai</p>
                </button>
                <button @click="tab = 'participants'" :class=" tab == 'participants' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all" >
                    <template x-if="tab == 'participants'" >
                        <x-icons.user-fill  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'participants'" >
                        <x-icons.user-fill class="fill-grey-400 transition-all w-5 " />
                    </template>
                    <p :class="tab == 'participants' ? 'text-black' : 'text-grey-400' " class="font-mediumtransition-all ml-2 text-sm" >Peserta</p>
                </button>
                <button @click="tab = 'import'" :class=" tab == 'import' ? 'border-b-[3px]' : 'border-0' " class="flex items-center border-primary pb-2 px-1 transition-all" >
                    <template x-if="tab == 'import'" >
                        <x-icons.upload  class=" fill-[#09244B] w-5 transition-all" />
                    </template>
                    <template x-if="tab != 'import'" >
                        <x-icons.upload class="fill-grey-400 transition-all w-5 " />
                    </template>
                    <p :class="tab == 'import' ? 'text-black' : 'text-grey-400' " class="font-mediumtransition-all ml-2 text-sm" >Import Kelas</p>
                </button>
            </div>
            @else
            <div class="h-4" ></div>
            @endif
        </div>

        <div class="px-8">
            <div x-show="tab == 'proggress'" class="pt-6" >
                {{-- <div
                    class="flex items-start overflow-hidden bg-white px-8 py-4 rounded-xl my-6 transition-[height] duration-1000"
                    :class="!showAnnouncement ? 'h-[80px]' : 'h-fit'"
                    style="box-shadow: 0px 3px 6px 0px rgba(16, 24, 40, 0.10);" 
                >
                    <img src="{{ asset('/assets/icons/pengumuman.svg') }}" alt="">
                    <div class="pt-3 flex flex-col w-full" >
                        <template x-if="showAnnouncement">
                            <input type="text" class="focus:placeholder:visible peer font-medium ml-4 caret-primary focus:bg-transparent  focus:outline-none" placeholder="Judul"  autofocus>
                        </template>    
                        <template x-if="!showAnnouncement">
                            <p class="ml-4 font-medium text-grey-700 " >Umumkan sesuatu di Kelas anda</p>
                        </template>    
                        <textarea 
                            placeholder="keterangan" 
                            class="
                                placeholder:invisible 
                                resize-none 
                                peer-focus:placeholder:visible  
                                ml-4 
                                mt-2 
                                text-sm 
                                focus:outline-none 
                                caret-primary 
                                focus:border-none
                            "
                            :class="showAnnouncement ? 'visible' : 'invisible'"
                            cols="30" 
                            rows="5"
                        ></textarea>
                    </div>
                </div> --}}
    
                @foreach ($sections as $i => $section)
                <div class="bg-white px-8 py-5 rounded-xl mb-3" >
                    <div class="flex">
                        <p class="font-semibold text-lg mr-1" >
                            @if (empty($section->name))
                            {{ $i == 0 ? 'General' : 'Topic '. $i }} 
                            @else
                            {{ $section->name  }}
                            @endif
                        </p>
                        @if ($role != 'student')
                        <button @click="topic.edit(@js($section->id),@js($section->name))" >
                            <img src="{{ asset('assets/icons/edit-2.svg') }}" alt="">
                        </button>
                        <button @click="activity.show(@js($section->section))" class="btn-icon-light w-8 h-8 ml-auto hidden md:flex" >
                            <x-icons.plus class="w-4 fill-primary" />
                        </button>
                        <div class="relative" >
                            <button :class="{ 'bg-gray-200': dropdownSection.includes(@js($section->id)) }" @click="toggleDropdownSection(@js($section->id))" class="w-8 h-8 ml-2 hidden md:flex group rounded" >
                                <x-icons.more-svg class="fill-primary" />
                            </button>
                            <ul
                                x-show="dropdownSection.includes(@js($section->id))"
                                x-transition:enter="transition ease duration-300"
                                x-transition:enter-start="opacity-0 scale-75 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease duration-300"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-2 scale-75"
                                class="absolute w-[200px] z-10 mt-2 bg-white rounded-lg py-3 px-3 right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform group-hover:opacity-100 group-hover:scale-y-100"
                            >
                                <li @click="deleteTopic(@js($section->id))" class="text-xs cursor-pointer rounded-lg hover:bg-grey-100 px-3 py-2 text-left" >
                                    Hapus Pertemuan
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                    @foreach ($section->modules as $mod_idx => $module)
                    @php
                        switch ($module->modname) {
                            case 'quiz':
                                $icon = asset('assets/icons/kuis.svg');
                                $detail_url = "/course/{$course->shortname}/activity/quiz/detail/{$module->id}";
                                break;
                            case 'url':
                                $icon = asset('assets/icons/url.svg');
                                $detail_url = "";
                                break;
                            case 'assign':
                                $icon = asset('assets/icons/penugasan.svg');
                                $detail_url = "/course/{$course->shortname}/activity/assignment/detail/{$module->id}";
                                break;
                            case 'attendance':
                                $icon = asset('assets/icons/kehadiran.svg');
                                $detail_url = "/course/{$course->shortname}/activity/attendance/detail/{$module->id}";
                                break;
                            case 'resource':
                                $icon = asset('assets/icons/berkas_md.svg');
                                $detail_url = "";
                                break;
                        }
                    @endphp
                    <div class="flex border hover:bg-grey-100 items-start border-grey-300 p-5 rounded-xl mt-5" >
                        <img src="{{ $icon }}" class="mr-3 w-10" alt="">
                        <div>
                            @switch($module->modname)
                                @case('url')
                                    <a target="blank" href="{{ $module->url }}" class="text font-semibold mb-1" >
                                        {{ $module->name }}
                                    </a>
                                    @break
                                @case('resource')
                                    @if (count($module->file) == 1)
                                    <a target="blank" href="{{ $module->file[0]->file }}" class="text font-semibold mb-1" >
                                        {{ $module->name }}
                                    </a>
                                    @else
                                    <a target="blank" href="javascript:;" class="text font-semibold mb-1" >
                                        {{ $module->name }}
                                    </a>
                                    @endif
                                    @break
                                @default
                                <a wire:navigate.hover href="{{ $detail_url }}" class="text font-semibold mb-1" >
                                    {{ $module->name }}
                                </a>
                            @endswitch
                            <div class="text-sm" >
                                {!! $module->description !!}
                            </div>
                            @if ($module->modname == 'resource' && count($module->file) > 1)
                            <ul style="list-style-type: circle;" class="mt-2 ml-3" >
                                @foreach ($module->file as $file)
                                <li class="mb-1" >
                                    <a target="blank" class="text-sm font-medium text-blue-600 underline" href="{{ $file->file }}">{{ $file->name }}</a>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                        @if ($role != 'student')
                        <div class="relative ml-auto self-center">
                            <button type="button" @click="toggleDropdownModule({{ $module->id }})" class="w-8 h-8 ml-auto" >
                                <x-icons.more-svg class="fill-primary" />
                            </button>
                            <ul
                                x-show="dropdownModule.includes({{ $module->id }})"
                                x-transition:enter="transition ease duration-300"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease duration-300"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-2"
                                class="absolute w-[200px] z-10 mt-2 bg-white rounded-lg py-3 px-3 right-0 top-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform group-hover:opacity-100 group-hover:scale-y-100">
                                <li @click="editModule(@js($module->modname), {{ $module->instance }}, {{ $section->section }}, {{ $module->id }})" class="text-xs cursor-pointer rounded-lg hover:bg-grey-100 px-3 py-2 text-left" >
                                    Edit Aktivitas
                                </li>
                                <li @click="deleteModule({{ $module->id }})" class="text-xs cursor-pointer rounded-lg hover:bg-grey-100 px-3 py-2 text-left" >
                                    Hapus Aktivitas
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
    
            <div x-show="tab == 'value'" >
                <div class="bg-white px-8 py-6 my-6">
                    <div class="flex mb-4">
                        <select wire:model.live="grading_table_type" class="text-field w-[120px] ml-auto rounded " id="">
                            <option value="all">Semua</option>
                            <option value="attendance">Kehadiran</option>
                            <option value="assignment">Tugas</option>
                            <option value="quiz">Quiz</option>
                        </select>
                        <x-button 
                            class="ml-3 font-medium" 
                            @click="$wire.export()"
                        >
                            Export
                        </x-button>
                    </div>
                    <div wire:ignore >
                        <div class="ag-theme-quartz" style="height: 70vh;" id="grade-grid"></div>
                    </div>
                </div>
            </div>

            <div x-show="tab == 'participants'" >
                <div class="bg-white rounded-xl px-8 py-4 my-6">
                    <p class="font-semibold text-lg mb-2" >Peserta</p>
                    <table class="w-full font-medium" >
                        <tr>
                            <td style="width: 210px; height: 35px;" class="text-grey-500 text-sm" >Jumlah Peserta</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1" >:</span> {{ count($participants) }} Orang </td>
                        </tr>
                        <tr>
                            <td style="width: 210px; height: 35px;" class="text-grey-500 text-sm" >Nama Pengajar</td>
                            <td class="text-[#121212] text-sm" > <span class="mr-1" >:</span> {{ $teacher->fullname }} </td>
                        </tr>
                    </table>
                </div>
                <div class="bg-white p-5 mt-6 rounded-xl mb-10">
                    <table class="w-full" >
                        <thead class="table-head" >
                            <tr>
                                <td class="" >No.</td>
                                <td class="" >Nama/Nim</td>
                                <td class="" >Email</td>
                            </tr>
                        </thead>
                        <tbody class="table-body" >
                            @foreach ($participants as $i => $student)
                            <tr>
                                <td class="w-[80px]" >{{ $i+1 }}</td>
                                <td class="w-[280px]" >
                                    <div class="flex items-center" >
                                        <img src="{{ asset('assets/images/avatar.webp') }}" class="w-[40px] h-[40px] rounded-full object-cover mr-3" alt="">
                                        <div>
                                            <p class="mb-1">{{ $student->fullname }}</p>
                                            <span class="text-grey-500 " >{{ $student->nim }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="" >
                                    {{ $student->email }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab == 'import'" >
                <div class="my-6 bg-white px-8 py-4 rounded-xl" >
                    <p class="font-semibold text-lg mb-2" >Import Kelas</p>
                    <label for="wordlimit" class="" >
                        <span class="block label text-gray-600 text-[12px] mb-1" >Pilih Matakuliah</span>
                        <select wire:change="get_imported_course_info($event.target.value)" name="assignsubmission_file_maxsizebytes" class="text-field w-[250px]" >
                            <option value="" >-- Pilih Jumlah Maksimal --</option>
                            @foreach ($enrolled_course ?? [] as $course)
                            <option value="{{ $course->id }}" >{{ $course->fullname }}</option>
                            @endforeach
                        </select>
                    </label>

                    <p class="font-semibold text-lg mb-2 mt-3" >Konten</p>

                    @foreach ($imported_course_sections ?? [] as $i => $importedSection)
                    <div class="bg-white px-8 py-5 rounded-xl mb-3 border-grey-300 border" >
                        <div class="flex items-center">
                            <input wire:change="handle_checked_imported_section({{ $importedSection->id }})" @checked(array_key_exists($importedSection->id, $selected_imported_contents->toArray())) type="checkbox" class="checkbox w-[18px] h-[18px]">
                            <p class="font-semibold text-lg ml-2" >
                                @if (empty($importedSection->name))
                                {{ $i == 0 ? 'General' : 'Topic '. $i }} 
                                @else
                                {{ $importedSection->name  }}
                                @endif
                            </p>
                            
                        </div>
                        @foreach ($importedSection->modules as $importedModule)
                        @php
                            switch ($importedModule->modname) {
                                case 'quiz':
                                    $icon = asset('assets/icons/kuis.svg');
                                    break;
                                case 'url':
                                    $icon = asset('assets/icons/url.svg');
                                    break;
                                case 'assign':
                                    $icon = asset('assets/icons/penugasan.svg');
                                    break;
                                case 'attendances':
                                    $icon = asset('assets/icons/kehadiran.svg');
                                    break;
                                case 'resource':
                                    $icon = asset('assets/icons/berkas_md.svg');
                                    break;
                            }
                        @endphp
                        <div class="flex border hover:bg-grey-100 items-center border-grey-300 p-5 rounded-xl mt-5" >
                            <input  
                                @checked(!empty($selected_imported_contents->get($importedSection->id)) ? in_array($importedModule->id, $selected_imported_contents->get($importedSection->id)) : false) 
                                type="checkbox" 
                                class="checkbox w-[18px] h-[18px]"
                                wire:change="handle_checked_imported_section_module({{ $importedSection->id }}, {{ $importedModule->id }})"
                            >
                            <img src="{{ $icon ?? '' }}" class="mx-3 w-10" alt="">
                            <div>
                                <p>{{ $importedModule->name }}</p>
                                <div class="text-sm" >
                                    {!! $importedModule->description !!}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-end gap-3 mt-4" >
                    <x-button wire:click="import_class" >
                        Import
                    </x-button>
                </div>
            </div>

        </div>


        <form wire:submit="change_section_title" >
            <x-modal
                show="topic.isEdit"
                onClose="topic.isEdit = false"
                title="Edit Pertemuan"
            >
                <label for="" class="block mb-2 text-sm font-medium text-grey-700">Judul Pertemuan</label>
                <input wire:model.live="topic.text" type="text" class="text-field text-field-base bg-grey-100 py-[10px] text-base w-[410px]" placeholder="Masukan judul" >
                <x-slot:footer>
                    <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                        <x-button
                            class="mr-2"
                            type="submit"
                        >
                            Simpan
                        </x-button>
                        <x-button
                            variant="outlined"
                        >
                            Batal
                        </x-button>
                    </div>
                </x-slot>
            </x-modal>
        </form>

        <x-modal 
            title="Tambah Aktivitas" 
            show="activity.modal" 
            onClose="activity.modal = false"
        >
            <label for="kehadiran" class="flex items-center mb-4" >
                <input x-model="activity.current" value="attendance" name="activity" id="kehadiran" type="radio" class="radio">
                <img src="{{ asset('assets/icons/kehadiran.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Kehadiran</span>
            </label>
    
            <label for="berkas" class="flex items-center mb-4" >
                <input x-model="activity.current" value="file" name="activity" id="berkas" type="radio" class="radio">
                <img src="{{ asset('assets/icons/berkas_lg.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Berkas</span>
            </label>
    
            <label for="penugasan" class="flex items-center mb-4" >
                <input x-model="activity.current" value="assignment" name="activity" id="penugasan" type="radio" class="radio">
                <img src="{{ asset('assets/icons/penugasan.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Penugasan</span>
            </label>
    
            <label for="kuis" class="flex items-center mb-4" >
                <input x-model="activity.current" value="quiz" name="activity" id="kuis" type="radio" class="radio">
                <img src="{{ asset('assets/icons/kuis.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Kuis</span>
            </label>
    
            <label for="url" class="flex items-center" >
                <input x-model="activity.current" value="url" name="activity" id="url" type="radio" class="radio">
                <img src="{{ asset('assets/icons/url.svg') }}" alt="" class="mx-[14px] w-12">
                <span class="font-medium" >Url</span>
            </label>
            
            <x-slot:footer>
                <div class="flex items-center justify-end px-6 py-4 bg-grey-100" >
                    <x-button @click="createActivity()" class="text-sm px-3 mr-2" >Simpan</x-button>
                    <x-button
                        variant="outlined"
                        @click="activity.modal = false"
                    >
                        Batal
                    </x-button>
                </div>
            </x-slot:footer>

        </x-modal>

        <x-toast/>

    </div>

    @if ($role != 'student')
    @assets
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    @endassets
    @endif

    @script
    <script>

        Alpine.data('pages', (course = null) => ({
            course,
            showAnnouncement: false,
            tab: 'proggress',
            activity: {
                modal: false,
                section: null,
                current: null,
                show(section){
                    this.section = section
                    this.modal = true
                },
            },
            topic: {
                isEdit: false,
                loading: false,
                current: {
                    id: null,
                    text: null,
                },
                edit: function(id, text){
                    $wire.$set('topic.id', id)
                    $wire.$set('topic.text', text)
                    this.isEdit = true
                }
            },
            dropdownSection: [],
            dropdownModule: [],
            toggleDropdownSection(id){
                if(this.dropdownSection.includes(id))
                    this.dropdownSection = this.dropdownSection.filter(e => e !== id)
                else
                    this.dropdownSection.push(id)
            },
            toggleDropdownModule(id){
                if(this.dropdownModule.includes(id))
                    this.dropdownModule = this.dropdownModule.filter(e => e !== id)
                else
                    this.dropdownModule.push(id)
            },
            deleteTopic(id){
                this.dropdownSection = this.dropdownSection.filter(e => e !== id)
                Livewire.dispatch('delete-section', { id })
            },
            createActivity(){
                // window.location.href = `/course/${this.course.shortname}/activity/create/${this.activity.current}/section/${this.activity.section}`
                Livewire.navigate(`/course/${this.course.shortname}/activity/create/${this.activity.current}/section/${this.activity.section}`)
            },
            editModule(mod, id, section, cm = null){
                console.log({ mod, id})

                if(mod == 'resource'){
                    Livewire.navigate(`/course/${this.course.shortname}/activity/update/${mod}/instance/${id}/section/${section}?cm=${cm}`)
                    // window.location.href = `/course/${this.course.shortname}/activity/update/${mod}/instance/${id}/section/${section}?cm=${cm}`
                } else {
                    // window.location.href = `/course/${this.course.shortname}/activity/update/${mod}/instance/${id}/section/${section}`
                    Livewire.navigate(`/course/${this.course.shortname}/activity/update/${mod}/instance/${id}/section/${section}`)
                }

            },
            deleteModule(id){
                this.dropdownModule = this.dropdownModule.filter(e => e !== id)
                $wire.$dispatch('delete-module', { id })
            }
        }))

        Livewire.on('title-change', () => {
            console.log('sdfds')
        })

        Livewire.on('notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })

        Livewire.on('notify-delay', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })

        Livewire.on('section-deleted-notify', ([ type, message ]) => {
            Alpine.store('toast').show = true
            Alpine.store('toast').type = type
            Alpine.store('toast').message = message
            setTimeout(() => {
                Alpine.store('toast').show = false
            }, 2000);
        })
        
    </script>
    @endscript

    @if ($role != 'student')
    @script
    <script>

        const fixColumn = [
            {
                field: 'fullname',
                pinned: 'left',
                headerName: 'Mahasiswa',
                cellRenderer: params => {
                    return `<p class="font-medium" style="line-height: 20px;" >${params.value} <br> <span class="text-grey-600 font-regular" >${params.data.nim}</span></p>`
                }
            },
        ]


        const gridOptions = {
            rowData: [],
            columnDefs: [],
            rowHeight: 60,
            defaultColDef: {
                cellStyle: { 
                    display: 'flex',
                    alignItems: 'center'
                },
            },
        };
        const myGridElement = document.querySelector('#grade-grid');
        const grid = agGrid.createGrid(myGridElement, gridOptions);

        Livewire.on('init-table', ([ grades, metaData ]) => {

            const { quiz, assignment, attendance } = metaData

            console.log({ grades, metaData })

            var columnDefs = [
                ...fixColumn
            ]

            if(metaData.type == 'attendance'){
                columnDefs = [
                    ...columnDefs,
                    ...Array.from({ length: attendance.length }, (_, index) => index)
                    .map(e => ({ 
                        field: `attendance_grades.attendance_${e}.grade`,
                        headerName: attendance[e],
                        width: 140,
                    }))
                ]
            }

            if(metaData.type == 'assignment'){
                columnDefs = [
                    ...columnDefs,
                    ...Array.from({ length: assignment.length }, (_, index) => index)
                    .map(e => ({ 
                        field: `assignment_grades.assignment_${e}.grade`,
                        headerName: assignment[e],
                        width: 140,
                    }))
                ]
            }

            if(metaData.type == 'quiz'){
                columnDefs = [
                    ...columnDefs,
                    ...Array.from({ length: quiz.length }, (_, index) => index)
                    .map(e => ({ 
                        field: `quiz_grades.quiz_${e}.grade`,
                        headerName: quiz[e],
                        width: 140,
                    }))
                ]
            }

            if(metaData.type == 'all'){
                columnDefs = [
                    ...columnDefs,
                    {
                        headerName: 'Kehadiran',
                        children: Array.from({ length: attendance.length }, (_, index) => index)
                            .map(e => ({ 
                                field: `attendance_grades.attendance_${e}.grade`,
                                headerName: attendance[e],
                                width: 140,
                            }))
                    },
                    {
                        headerName: 'Quiz',
                        children: Array.from({ length: quiz.length }, (_, index) => index)
                            .map(e => ({ 
                                field: `quiz_grades.quiz_${e}.grade`,
                                headerName: quiz[e],
                                width: 140,
                            }))
                    },
                    {
                        headerName: 'Tugas', 
                        children: Array.from({ length: assignment.length }, (_, index) => index)
                        .map(e => ({ 
                            field: `assignment_grades.assignment_${e}.grade`,
                            headerName: assignment[e],
                            width: 140,
                        }))
                    }
                ]
            }

            grid.setGridOption('columnDefs', columnDefs)
            grid.setGridOption('rowData', grades)
        })

    </script>
    @endscript
    @endif

    @endvolt
</x-layouts.app>