<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Helpers\GradeHelper;
use App\Helpers\QuizGraderHelper;
use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use App\Models\Event;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\GradeItemHistory;
use App\Models\Module;
use App\Models\QtypeEssaiOption;
use App\Models\QtypeMultiChoiceOption;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuestionAttempt;
use App\Models\QuestionAttemptStep;
use App\Models\QuestionBankEntry;
use App\Models\QuestionRefenrence;
use App\Models\QuestionTrueFalse;
use App\Models\QuestionUsage;
use App\Models\QuestionVersion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSection;
use App\Models\QuizSlot;
use App\Models\Role;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class QuizController extends Controller
{

    protected $quizGraderHelper;
    protected $gradeHelper;

    public function __construct(QuizGraderHelper $quizGraderHelper, GradeHelper $gradeHelper)
    {
        $this->quizGraderHelper = $quizGraderHelper;
        $this->gradeHelper = $gradeHelper;
    }

    public function findById(Quiz $quiz){
        $instance = new \stdClass();

        $instance->id = $quiz->id;
        $instance->name = $quiz->name;
        $instance->description = $quiz->intro;

        $start = Carbon::createFromTimestamp($quiz->timeopen)->timezone('Asia/Makassar');
        $end = Carbon::createFromTimestamp($quiz->timeclose)->timezone('Asia/Makassar');

        $instance->start_date = $start->translatedFormat('Y-m-d');
        $instance->end_date = $end->translatedFormat('Y-m-d');

        $instance->start_time = $start->translatedFormat('H:i');
        $instance->end_time = $end->translatedFormat('H:i');

        if($start->diffInDays($end) == 0){
            $instance->time_type = 'time';
        } else {
            $instance->time_type = 'date';
        }

        if ($quiz->timelimit % 3600 == 0) {
            $instance->time_limit_type = 'hour';
            $instance->time_limit = $quiz->timelimit / 3600;
        } else {
            $instance->time_limit_type = 'minute';
            $instance->time_limit = $quiz->timelimit / 60;
        }

        $cm = CourseModule::where('instance', $quiz->id)
        ->where('module', Module::where('name', 'quiz')->first()->id)
        ->where('course', $quiz->course)
        ->first();

        $cs = CourseSection::where('id', $cm->section)->first(['section', 'name']);

        $instance->course_section = $cs;

        return response()->json([
            'message' => 'Success',
            'data' => $instance
        ], 200);
    }

    public function store(Request $request, $shortname){
        $module = Module::where('name', 'quiz')->first();
        $course = Course::where('shortname', $shortname)->first();

        $start_date = Carbon::parse($request->start_date . ' '. $request->start_time );
        $due_date = Carbon::parse($request->end_date . ' '. $request->end_time );

        if($request->time_limit_type == 'minute'){
            $time_limit = $request->time_limit * 60;
        } else {
            $time_limit = $request->time_limit * 3600;
        }

        DB::beginTransaction(); 

        try {

            $instance = Quiz::create([
                'course' => $course->id,
                'name' => $request->name,
                'timeopen' => $start_date->unix(),
                'timeclose' => $due_date->unix(),
                'timelimit' => $time_limit,
                'overduehandling' => 'autoabandon', // Penanganan keterlambatan
                'graceperiod' => 0, // Tidak ada periode toleransi
                'grade' => 10, // Skor maksimal
                'attempts' => 0, // Upaya pengerjaan tidak terbatas
                'grademethod' => 1, // Metode penilaian (1 = nilai tertinggi)
                'questionsperpage' => $request->questionperpage, // Jumlah pertanyaan per halaman
                'navmethod' => 'free', // Metode navigasi bebas
                'shuffleanswers' => 1, // Jawaban diacak
                'preferredbehaviour' => 'deferredfeedback', // Perilaku umpan balik ditunda
                'canredoquestions' => 0, // Tidak dapat mengulangi pertanyaan
                'attemptonlast' => 0, // Tidak melanjutkan dari upaya terakhir
                'showuserpicture' => 0, // Tidak menampilkan foto pengguna
                'decimalpoints' => 2, // Jumlah angka desimal untuk nilai
                'questiondecimalpoints' => -1, // Mengikuti default untuk angka desimal pertanyaan
                'intro' => $request->description, // Deskripsi kuis dalam format HTML
                'introformat' => 1, // Format pengantar (1 = HTML)
            ]);


            QuizSection::create([
                'quizid' =>  $instance->id,
                'firstslot' => 1,
                'heading' => '',
                'shufflequestions' => 0
            ]);

            $cm = CourseHelper::addCourseModule($course->id, $module->id, $instance->id);
            $ctx = CourseHelper::addContext($cm->id, $course->id);
            CourseHelper::addCourseModuleToSection($course->id, $cm->id, $request->section);

            Event::create([
                'type' => 0,
                'description' => '<div class="no-overflow"><p>sfdafas</p></div>',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => $request->user()->id,
                'modulename' => 'quiz',
                'instance' => $instance->id,
                'timestart' => $start_date->unix(),
                'timeduration' => 0,
                'timesort' => $start_date->unix(),
                'visible' => 1,
                'eventtype' => 'open',
                'priority' => null,
                'name' => $instance->name . ' opens',
                'component' => null,
                'timemodified' => now()->timestamp,
            ]);

            $GradeCategory = GradeCategory::firstWhere("courseid", $course->id);
            $grade = GradeItem::create([
                'courseid' => $course->id,
                'categoryid' => $GradeCategory->id,
                'itemname' => $instance->name,
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $instance->id,
                'itemnumber' => 0,
                'iteminfo' => null,
                'idnumber' => '',
                'calculation' => null,
                'gradetype' => 1,
                'grademax' => 10,
                'grademin' => 0,
                'scaleid' => null,
                'outcomeid' => null,
                'gradepass' => 0,
                'multfactor' => 1,
                'plusfactor' => 0,
                'aggregationcoef' => 0,
                'aggregationcoef2' => 0,
                'sortorder' => 11,
                'display' => 0,
                'decimals' => null,
                'locked' => 0,
                'locktime' => 0,
                'needsupdate' => 1,
                'weightoverride' => 0,
                'timecreated' => now()->timestamp,
                'timemodified' => now()->timestamp,
                'hidden' => 0,
            ]);

            GradeItemHistory::create([
                'courseid' => $course->id,
                'categoryid' => $GradeCategory->id,
                'itemname' => $instance->name,
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $course->id,
                'itemnumber' => 0,
                'iteminfo' => null,
                'idnumber' => '',
                'calculation' => null,
                'gradetype' => 1,
                'grademax' => 10.00000,
                'grademin' => 0.00000,
                'action' => 1,
                'oldid' => $grade->id,
                'loggeduser' => $request->user()->id,
                'timemodified' => now()->timestamp,
                'needsupdate' => 1
            ]);

            DB::commit();
            
            GlobalHelper::rebuildCourseCache($course->id);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

        

    }

    public function update(Request $request, Quiz $quiz){
        $start_date = Carbon::parse($request->start_date . ' '. $request->start_time );
        $due_date = Carbon::parse($request->end_date . ' '. $request->end_time );

        if($request->time_limit_type == 'minute'){
            $time_limit = $request->time_limit * 60;
        } else {
            $time_limit = $request->time_limit * 3600;
        }

        DB::beginTransaction(); 

        try {
            $quiz->update([
                'name' => $request->name,
                'timeopen' => $start_date->unix(),
                'timeclose' => $due_date->unix(),
                'timelimit' => $time_limit,
                'intro' => $request->description,
            ]);

            Event::where('modulename', 'quiz')
                ->where('instance', $quiz->id)
                ->update([
                    'timestart' => $start_date->unix(),
                    'timesort' => $start_date->unix(),
                    'name' => $quiz->name . ' opens',
                    'timemodified' => now()->timestamp,
                ]);

            $grade = GradeItem::where('itemmodule', 'quiz')
                ->where('iteminstance', $quiz->id)
                ->first();

            $grade->update([
                'itemname' => $quiz->name,
                'timemodified' => now()->timestamp,
            ]);

            GradeItemHistory::create([
                'courseid' => $grade->courseid,
                'categoryid' => $grade->categoryid,
                'itemname' => $quiz->name,
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $quiz->id,
                'itemnumber' => 0,
                'iteminfo' => null,
                'idnumber' => '',
                'calculation' => null,
                'gradetype' => 1,
                'grademax' => 10.00000,
                'grademin' => 0.00000,
                'action' => 2,
                'oldid' => $grade->id,
                'loggeduser' => $request->user()->id,
                'timemodified' => now()->timestamp,
                'needsupdate' => 1
            ]);

            DB::commit();
            
            GlobalHelper::rebuildCourseCache($quiz->course);

            return response()->json([
                'message' => 'Quiz updated successfully',
                'data' => $quiz
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getQuizDetail(Quiz $quiz){

        $cm = CourseModule::where('instance', $quiz->id)
        ->where('module', Module::where('name', 'quiz')->first()->id)
        ->where('course', $quiz->course)
        ->first();

        $ctx = Context::where('instanceid', $cm->id)->where('contextlevel', 70)->first();

        $role = Role::where('shortname', 'student')->first();

        $students = DB::connection('moodle_mysql')->table('mdl_user as u')
        ->select([
            DB::raw("DISTINCT CONCAT(u.id, '#', COALESCE(quiza.attempt, 0)) AS uniqueid"),
            DB::raw("(CASE 
                WHEN (quiza.state = 'finished' AND NOT EXISTS (
                    SELECT 1 
                    FROM mdl_quiz_attempts qa2
                    WHERE qa2.quiz = quiza.quiz 
                      AND qa2.userid = quiza.userid 
                      AND qa2.state = 'finished' 
                      AND (
                        COALESCE(qa2.sumgrades, 0) > COALESCE(quiza.sumgrades, 0) OR
                        (COALESCE(qa2.sumgrades, 0) = COALESCE(quiza.sumgrades, 0) AND qa2.attempt < quiza.attempt)
                      )
                )) THEN 1 ELSE 0 
            END) AS gradedattempt"),
            'quiza.uniqueid as usageid',
            'quiza.id as attempt',
            'u.id as userid',
            'u.idnumber',
            'u.picture',
            'u.imagealt',
            'u.institution',
            'u.department',
            'u.email',
            'u.username',
            'u.firstnamephonetic',
            'u.lastnamephonetic',
            'u.middlename',
            'u.alternatename',
            'u.firstname',
            'u.lastname',
            'quiza.state',
            'quiza.sumgrades',
            'quiza.timefinish',
            'quiza.timestart',
            DB::raw("CASE 
                WHEN quiza.timefinish = 0 THEN NULL
                WHEN quiza.timefinish > quiza.timestart THEN quiza.timefinish - quiza.timestart
                ELSE 0 
            END AS duration"),
            DB::raw("COALESCE((
                SELECT MAX(qqr.regraded)
                FROM mdl_quiz_overview_regrades qqr
                WHERE qqr.questionusageid = quiza.uniqueid
            ), -1) AS regraded")
        ])
        ->leftJoin('mdl_quiz_attempts as quiza', function ($join) use ($quiz) {
            $join->on('quiza.userid', '=', 'u.id')
                 ->where('quiza.quiz', '=', $quiz->id);
        })
        ->join('mdl_user_enrolments as ej1_ue', 'ej1_ue.userid', '=', 'u.id')
        ->join('mdl_enrol as ej1_e', function ($join) use ($quiz) {
            $join->on('ej1_e.id', '=', 'ej1_ue.enrolid')
                 ->where('ej1_e.courseid', '=', $quiz->course);
        })
        ->joinSub(
            DB::connection('moodle_mysql')->table('mdl_role_assignments')
                ->select('userid')
                ->distinct()
                ->whereIn('contextid', explode("/", $ctx->path))
                ->whereIn('roleid', [$role->id]),
            'ra',
            'ra.userid',
            '=',
            'u.id'
        )
        ->where('quiza.preview', '=', 0)
        ->whereNotNull('quiza.id')
        ->where('u.deleted', '=', 0)
        ->where('u.id', '<>', 1)
        ->orderBy('quiza.id', 'asc')
        ->get();

        $formatted_students = $students->map(function($student) use ($quiz) {

            $quiz_grade = DB::connection('moodle_mysql')->table('mdl_quiz_grades')
            ->where(
                [
                    'userid' => $student->userid,
                    'quiz' => $quiz->id
                ]
            )->first();

            if($quiz_grade) {
                $final_grade = number_format($quiz_grade->grade * 10, 2, ',');
            } else {
                $final_grade = "Belum dinilai";
            }


            return [
                'name' => $student->firstname . ' ' . $student->lastname,
                'email' => $student->email,
                'username' => $student->username,
                'grade' => $final_grade,
                'duration' => $student->duration,
                'attempt' => $student->attempt,
                'userid' => $student->userid,
                'usageid' => $student->usageid,
                'timestart' => Carbon::parse($student->timestart)->timezone('Asia/Makassar')->translatedFormat('H:i'),
                'timefinish' => $student->timefinish == 0 ? "-" : Carbon::parse($student->timefinish)->timezone('Asia/Makassar')->translatedFormat('H:i'),
            ];
        });

        $instance = new stdClass();
        $instance->name = $quiz->name;
        $instance->description = $quiz->intro;
        $instance->timeclose = $quiz->timeclose == 0 ? "-" : Carbon::parse($quiz->timeclose)->timezone('Asia/Makassar')->translatedFormat('d-m-Y H:i');
        $instance->duration = ($quiz->timelimit / 60) . " Menit";
        $instance->students = $formatted_students;

        return response()->json([
            'message' => 'Success',
            'data' => $instance
        ]);

    }

    public function getStudentAnswer(Quiz $quiz, $usageid){

        $questions = DB::connection('moodle_mysql')->table('mdl_question_attempts as qa')
        ->joinSub(
            DB::connection('moodle_mysql')->table('mdl_question_attempt_steps')
                ->select('questionattemptid', DB::raw('MAX(sequencenumber) as max_seq'))
                ->groupBy('questionattemptid'),
            'latest',
            'qa.id',
            '=',
            'latest.questionattemptid'
        )
        ->join(
            'mdl_question_attempt_steps as qas',
            function ($join) {
                $join->on('qas.questionattemptid', '=', 'latest.questionattemptid')
                    ->on('qas.sequencenumber', '=', 'latest.max_seq');
            }
        )
        ->join('mdl_question as q', 'q.id', '=', 'qa.questionid')
        ->where('qa.questionusageid', $usageid)
        ->select('qa.*', 'qas.*',  'qas.id as attemptstepid','q.qtype as question_type')
        ->get();

        $quizAttempt = QuizAttempt::where('mdl_quiz_attempts.uniqueid', $usageid)
        ->where('mdl_quiz_attempts.quiz', $quiz->id)
        ->join('mdl_user as u', 'u.id', '=', 'mdl_quiz_attempts.userid')
        ->select([
            'mdl_quiz_attempts.*',
            DB::raw("CONCAT(u.firstname, ' ', u.lastname) as fullname"),
            'u.username as nim',
            'u.id as studentid'
        ])
        ->first();

        $timeStart = Carbon::parse($quizAttempt->timestart);
        $timeFinish = Carbon::parse($quizAttempt->timefinish);

        $quizAttempt->formatted_duration = $timeFinish->diffForHumans($timeStart, CarbonInterface::DIFF_ABSOLUTE);
        $quizAttempt->formatted_date = $timeStart->translatedFormat('d F Y');

        $quiz_grade = DB::connection('moodle_mysql')->table('mdl_quiz_grades')
        ->where(
            [
                'userid' => $quizAttempt->userid,
                'quiz' => $quiz->id
            ]
        )->first();

        if($quiz_grade) {
            $quizAttempt->grade = number_format($quiz_grade->grade * 10, 2, ',');
        } else {
            $quizAttempt->grade = 0;
        }

        foreach ($questions as $q) {
            $q->formatted_question = explode("\r\n", $q->questionsummary)[0];
            $q->formatted_rightanswer = str_replace(["\r", "\n"], '', $q->rightanswer);
            $q->fraction = (float)$q->fraction;
            if($q->question_type != 'essay'){
                $optionsText = substr( $q->questionsummary, strpos( $q->questionsummary, "\r\n:") + 3);
                $options = preg_split('/\n|;/', $optionsText);
                $options = array_values(array_filter(array_map('trim', $options)));
                $q->formatted_options = $options;
            }

            if($q->question_type == 'essay'){
                $q->essay_answer = DB::connection('moodle_mysql')->table('mdl_question_attempt_steps as qas')
                ->leftJoin('mdl_question_attempt_step_data as qasd', 'qasd.attemptstepid', '=', 'qas.id')
                ->select('qasd.value')
                ->where('qas.state', '=', 'complete')
                ->where('qas.questionattemptid', '=', $q->questionattemptid)
                ->where('qasd.name', '=', 'answer')
                ->where('qas.sequencenumber', '=', function ($query) use ($q) {
                    $query->selectRaw('MAX(sequencenumber)')
                        ->from('mdl_question_attempt_steps')
                        ->where('questionattemptid', '=', $q->questionattemptid)
                        ->where('state', '=', 'complete');
                })
                ->first()->value;
                $q->fraction_formatted = $q->fraction * 10;
            }

        }

        return response()->json([
            'message' => 'get student ansswerr success',
            'data' => [
                'student' => $quizAttempt,
                'questions' => $questions
            ]
        ]);

    }

    public function getQuizDetailForStudent(Request $request, Quiz $quiz){

        $cm = CourseModule::where('instance', $quiz->id)
        ->where('module', Module::where('name', 'quiz')->first()->id)
        ->where('course', $quiz->course)
        ->first();

        $instance = new stdClass();
        $instance->name = $quiz->name;
        $instance->description = $quiz->intro;
        $instance->timeclose = $quiz->timeclose == 0 ? "-" : Carbon::parse($quiz->timeclose)->timezone('Asia/Makassar')->translatedFormat('d-m-Y H:i');
        $instance->duration = ($quiz->timelimit / 60) . " Menit";

        $quiz_attempt = QuizAttempt::where('quiz', $quiz->id)
        ->where('userid', $request->user()->id)
        ->first();

        $instance->status = null;

        if($quiz_attempt){
            $timeStart = Carbon::parse($quiz_attempt->timestart);
            $timeFinish = Carbon::parse($quiz_attempt->timefinish);
            $instance->duration = $timeFinish->diffForHumans($timeStart, CarbonInterface::DIFF_ABSOLUTE);
            $instance->status = $quiz_attempt->state;
            
            $quiz_grade = DB::connection('moodle_mysql')->table('mdl_quiz_grades')
            ->where(
                [
                    'userid' => $request->user()->id,
                    'quiz' => $quiz->id
                ]
            )->first();
            
            if($quiz_grade) {
                $instance->grade = number_format($quiz_grade->grade * 10, 2, ',');
            } else {
                $instance->grade = "Belum dinilai";
            }

        }

        return response()->json([
            'message' => 'Success',
            'data' => $instance
        ]);

    }

    public function getBankQuestion(Request $request, $shortname){

        $perpage = $request->query('perpage') ?? 10;

        $course = Course::where('shortname', $shortname)->first();

        $ctx = Context::where('instanceid', $course->id)->where('contextlevel', 50)->first();

        $categories = DB::connection('moodle_mysql')->table('mdl_question_categories')
        ->where('contextid', $ctx->id)
        ->orderBy('id', 'DESC')
        ->get();

        $res = DB::connection('moodle_mysql')->table('mdl_question as q')
            ->join('mdl_question_versions as qv', 'qv.questionid', '=', 'q.id')
            ->join('mdl_question_bank_entries as qbe', 'qbe.id', '=', 'qv.questionbankentryid')
            ->join('mdl_question_categories as qc', 'qc.id', '=', 'qbe.questioncategoryid')
            ->where('q.parent', 0)
            ->where(function ($subQuery) {
                $subQuery->where('qv.status', 'ready')
                        ->orWhere('qv.status', 'draft');
            })
            ->where('qbe.questioncategoryid', $categories[0]->id)
            ->whereRaw('qv.version = (SELECT MAX(v.version)
                                        FROM mdl_question_versions v
                                        JOIN mdl_question_bank_entries be 
                                        ON be.id = v.questionbankentryid
                                        WHERE be.id = qbe.id)')
            ->orderBy('q.qtype', 'asc')
            ->orderBy('q.name', 'asc')
            ->select([
                'qv.status',
                'qc.id as categoryid',
                'qv.version',
                'qv.id as versionid',
                'qbe.id as questionbankentryid',
                'q.id',
                'q.qtype',
                'q.name',
                'qbe.idnumber',
                'q.createdby',
                'qc.contextid',
                'q.timecreated',
                'q.timemodified',
            ])
        ->paginate($perpage);

        return response()->json([
            'message' => 'Success',
            'data' => $res
        ]);

    }

    public function storeQuestion(Request $request, $shortname){
        $course = Course::where('shortname', $shortname)->first();

        $ctx = Context::where('instanceid', $course->id)->where('contextlevel', 50)->first();

        $categories = DB::connection('moodle_mysql')->table('mdl_question_categories')
        ->where('contextid', $ctx->id)
        ->orderBy('id', 'DESC')
        ->get();

        DB::connection('moodle_mysql')->beginTransaction();

        $qtype = $request->type;

        if($request->type == 'multiple_choice'){
            $qtype = 'multichoice';
        } 

        if($request->type == 'true_false'){
            $qtype = 'truefalse';
        } 

        try {
            //code...
            $question = Question::create([
                'parent' => 0,
                'name' => $request->name,
                'questiontext' => $request->description,
                'questiontextformat' => 1,
                'qtype' => $qtype,
                'stamp' => 'localhost:8888+241224110017+FsP2Eu',
                'generalfeedback' => '',
                'generalfeedbackformat' => 1,
                'timecreated' => now()->timestamp,
                'timemodified' => now()->timestamp,
                'createdby' => $request->user()->id,
                'modifiedby' => $request->user()->id,
            ]);
    
            $qbe = QuestionBankEntry::create([
                'questioncategoryid' => $categories[0]->id,
                'idnumber' => null,
                'ownerid' => $request->user()->id,
            ]);
    
            $qv = QuestionVersion::create([
                'questionbankentryid' => $qbe->id,
                'questionid' => $question->id,
                'version' => 1,
                'status' => 'ready',
            ]);
    
            if($qtype == 'multichoice'){
                $options = $request->questionConfig['options'];
                foreach ($options as $key => $value) {
                    $correct = 0;
                    if($value['isRight']){
                        $correct = 1;
                    }
                    QuestionAnswer::create([
                        'question' => $question->id,
                        'answer' => $value['value'],
                        'fraction' => $correct,
                        'feedback' => '',
                        'feedbackformat' => 1,
                    ]);
                }
    
                QtypeMultiChoiceOption::create([
                    'questionid' => $question->id,
                    'correctfeedback' => '<p>Jawaban anda benar</p>',
                    'partiallycorrectfeedback' => '<p>Jawaban anda benar sebagian</p>',
                    'incorrectfeedback' => '<p>Jawaban anda salah</p>',
                    'showstandardinstruction' => 0,
                    'single' => 1,
                    'answernumbering' => 'abc',
                    'shuffleanswers' => 1,
                    'correctfeedbackformat' => 1,
                    'partiallycorrectfeedbackformat' => 1,
                    'incorrectfeedbackformat' => 1,
                    'shownumcorrect' => 1,
                ]);
    
            }

            if($qtype == 'truefalse'){
                $trueAnswer = QuestionAnswer::create([
                    'question' => $question->id,
                    'answer' =>  'True',
                    'fraction' => $request->questionConfig['true']['isRight'] ? 1 : 0,
                    'feedback' => '',
                    'feedbackformat' => 1,
                ]);

                $falseAnswer = QuestionAnswer::create([
                    'question' => $question->id,
                    'answer' =>  'False',
                    'fraction' => $request->questionConfig['false']['isRight'] ? 1 : 0,
                    'feedback' => '',
                    'feedbackformat' => 1,
                ]);

                QuestionTrueFalse::create([
                    'question' => $question->id,
                    'trueanswer' => $trueAnswer->id,
                    'falseanswer' => $falseAnswer->id,
                    'showstandardinstruction' => 0
                ]);

            }

            if($qtype == 'essay'){
                QtypeEssaiOption::create([
                    'questionid' => $question->id,
                    'responseformat' => 'editor',
                    'responserequired' => 1,
                    'responsefieldlines' => 10,
                    'minwordlimit' => null,
                    'maxwordlimit' => null,
                    'attachments' => 0,
                    'attachmentsrequired' => 0,
                    'filetypeslist' => '',
                    'maxbytes' => 0,
                    'graderinfo' => '',
                    'graderinfoformat' => 1,
                    'responsetemplate' => '',
                    'responsetemplateformat' => 1,
                ]);
            }

            DB::connection('moodle_mysql')->commit();

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

        
    }

    public function deleteQuestion($shortname, Question $question){
        DB::connection('moodle_mysql')->beginTransaction();

        try {
        
            if($question->qtype == 'multichoice'){
                QtypeMultiChoiceOption::where('questionid', $question->id)->delete();
            }

            if($question->qtype == 'truefalse'){
                $trueFalse = QuestionTrueFalse::where('question', $question->id)->first();
                $trueFalse->delete();
            }

            if($question->qtype == 'essay'){
                QtypeEssaiOption::where('questionid', $question->id)->delete();
            }
            
            QuestionAnswer::where('question', $question->id)->delete();
            DB::connection('moodle_mysql')->table('mdl_question_hints')->where('questionid', $question->id)->delete();
            $qv = QuestionVersion::where('questionid', $question->id)->get();
            foreach ($qv as $qvv) {
                QuestionBankEntry::where('id', $qvv->questionbankentryid)->delete();
                $qvv->delete();
            }

            $question->delete();
            DB::connection('moodle_mysql')->commit();
        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getQuestionById($shortname, Question $question){
        $instance = new \stdClass();

        $instance->id = $question->id;
        $instance->name = $question->name;
        $instance->description = $question->questiontext;

        $instance->type = $question->qtype;

        if($question->qtype == 'multichoice'){
            $instance->type = 'multiple_choice';
        }

        if($question->qtype == 'truefalse'){
            $instance->type = 'true_false';
        }

        $instance->questionConfig = [
            'options' => [],
            'true' => [ 'isRight' => false ],
            'false' => [ 'isRight' => false ],
            'numberOfOptions' => 0
        ];

        if($question->qtype == 'multichoice'){
            $options = QuestionAnswer::where('question', $question->id)->get();
            $instance->questionConfig = [
                ...$instance->questionConfig,
                'options' => $options->map(function($item){
                    return [
                        'value' => $item->answer,
                        'isRight' => $item->fraction == 1 ? true : false
                    ];
                })->toArray(),
                'numberOfOptions' => $options->count()
            ];
        }

        if($question->qtype == 'truefalse'){
            $trueFalse = QuestionTrueFalse::where('question', $question->id)->first();
            $true = QuestionAnswer::where('id', $trueFalse->trueanswer)->first();
            $false = QuestionAnswer::where('id', $trueFalse->falseanswer)->first();
            $instance->questionConfig = [
                ...$instance->questionConfig,
                'true' => [
                    'isRight' => $true->fraction == 1 ? true : false
                ],
                'false' => [
                    'isRight' => $false->fraction == 1 ? true : false
                ]
            ];
        }

        return response()->json([
            'message' => 'Success',
            'data' => $instance
        ], 200);

    }

    public function updateQuestion(Request $request, $shortname, Question $question){

        $course = Course::where('shortname', $shortname)->first();

        $oldQv = QuestionVersion::where('questionid', $question->id)
            ->orderBy('version', 'desc')
            ->first();

        DB::connection('moodle_mysql')->beginTransaction();

        $qtype = $request->type;

        if($request->type == 'multiple_choice'){
            $qtype = 'multichoice';
        }

        if($request->type == 'true_false'){
            $qtype = 'truefalse';
        }

        try {
            //code...
            $question = Question::create([
                'parent' => 0,
                'name' => $request->name,
                'questiontext' => $request->description,
                'questiontextformat' => 1,
                'qtype' => $qtype,
                'stamp' => 1,
                'generalfeedback' => '',
                'generalfeedbackformat' => 1,
                'timecreated' => now()->timestamp,
                'timemodified' => now()->timestamp,
                'createdby' => $request->user()->id,
                'modifiedby' => $request->user()->id,
            ]);
    
            $qv = QuestionVersion::create([
                'questionbankentryid' => $oldQv->questionbankentryid,
                'questionid' => $question->id,
                'version' => $oldQv->version + 1,
                'status' => 'ready',
            ]);
    
            if($qtype == 'multichoice'){
                $options = $request->questionConfig['options'];
                foreach ($options as $key => $value) {
                    $correct = 0;
                    if($value['isRight']){
                        $correct = 1;
                    }
                    QuestionAnswer::create([
                        'question' => $question->id,
                        'answer' => $value['value'],
                        'fraction' => $correct,
                        'feedback' => '',
                        'feedbackformat' => 1,
                    ]);
                }
    
                QtypeMultiChoiceOption::create([
                    'questionid' => $question->id,
                    'correctfeedback' => '<p>Jawaban anda benar</p>',
                    'partiallycorrectfeedback' => '<p>Jawaban anda benar sebagian</p>',
                    'incorrectfeedback' => '<p>Jawaban anda salah</p>',
                    'showstandardinstruction' => 0,
                    'single' => 1,
                    'answernumbering' => 'abc',
                    'shuffleanswers' => 1,
                    'correctfeedbackformat' => 1,
                    'partiallycorrectfeedbackformat' => 1,
                    'incorrectfeedbackformat' => 1,
                    'shownumcorrect' => 1,
                ]);
    
            }

            if($qtype == 'truefalse'){
                $trueAnswer = QuestionAnswer::create([
                    'question' => $question->id,
                    'answer' =>  'True',
                    'fraction' => $request->questionConfig['true']['isRight'] ? 1 : 0,
                    'feedback' => '',
                    'feedbackformat' => 1,
                ]);

                $falseAnswer = QuestionAnswer::create([
                    'question' => $question->id,
                    'answer' =>  'False',
                    'fraction' => $request->questionConfig['false']['isRight'] ? 1 : 0,
                    'feedback' => '',
                    'feedbackformat' => 1,
                ]);

                QuestionTrueFalse::create([
                    'question' => $question->id,
                    'trueanswer' => $trueAnswer->id,
                    'falseanswer' => $falseAnswer->id,
                    'showstandardinstruction' => 0
                ]);

            }

            if($qtype == 'essay'){
                QtypeEssaiOption::create([
                    'questionid' => $question->id,
                    'responseformat' => 'editor',
                    'responserequired' => 1,
                    'responsefieldlines' => 10,
                    'minwordlimit' => null,
                    'maxwordlimit' => null,
                    'attachments' => 0,
                    'attachmentsrequired' => 0,
                    'filetypeslist' => '',
                    'maxbytes' => 0,
                    'graderinfo' => '',
                    'graderinfoformat' => 1,
                    'responsetemplate' => '',
                    'responsetemplateformat' => 1,
                ]);
            }

            DB::connection('moodle_mysql')->commit();

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

    }

    public function insertQuestionToQuiz(Request $request, $shortname, Quiz $quiz){

        $module = Module::where('name', 'quiz')->first();

        $course = Course::where('shortname', $shortname)->first();

        $cm = CourseModule::where('course', $course->id)
        ->where('module', $module->id)
        ->where('instance', $quiz->id)
        ->first();       

        $ctx = Context::where('instanceid', $cm->id)->where('contextlevel', 70)->first();

        $oldqs = QuizSlot::where('quizid', $quiz->id)->orderBy('slot', 'desc')->first();

        $slot = $oldqs ? $oldqs->slot + 1 : 1;
        $page = $oldqs ? $oldqs->page + 1 : 1;

        DB::connection('moodle_mysql')->beginTransaction();
        
        try {
            foreach ($request->questions as $question) {
                
                $qba = DB::connection('moodle_mysql')->table('mdl_question as q')
                ->join('mdl_question_versions as qv', 'qv.questionid', '=', 'q.id')
                ->join('mdl_question_bank_entries as qbe', 'qbe.id', '=', 'qv.questionbankentryid')
                ->select('qbe.*')
                ->where('q.id', '=', $question)
                ->get();

                $exists = QuestionRefenrence::where('questionbankentryid', $qba[0]->id)
                ->where('usingcontextid', $ctx->id)
                ->exists();

                if($exists){
                    continue;
                }
    
                $qs = QuizSlot::create([
                    'quizid' => $quiz->id,
                    'maxmark' => 1,
                    'slot' => $slot++,
                    'page' => $page++,
                ]);
        
                QuestionRefenrence::create([
                    'usingcontextid' => $ctx->id,
                    'component' => 'mod_quiz',
                    'questionarea' => 'slot',
                    'itemid' => $qs->id,
                    'questionbankentryid' => $qba[0]->id,
                    'version' => null,
                ]);
            }

            $quiz->update([
                'sumgrades' => DB::raw("COALESCE((SELECT SUM(maxmark) FROM mdl_quiz_slots WHERE quizid = mdl_quiz.id), 0)")
            ]);

            DB::connection('moodle_mysql')->commit();
            return response()->json([
                'message' => 'Questions added to quiz successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

    }

    public function getQuizQuestion($shortname, Quiz $quiz){

        $cm = CourseModule::where('instance', $quiz->id)
        ->where('module', Module::where('name', 'quiz')->first()->id)
        ->where('course', $quiz->course)
        ->first();

        $ctx = Context::where('instanceid', $cm->id)->where('contextlevel', 70)->first();

        $questions = DB::connection('moodle_mysql')->table('mdl_quiz_slots as slot')
        ->select([
            'slot.slot',
            'slot.id as slotid',
            'slot.page',
            'slot.maxmark',
            'slot.displaynumber',
            'slot.requireprevious',
            'qsr.filtercondition',
            'qv.status',
            'qv.id as versionid',
            'qv.version',
            'qr.version as requestedversion',
            'qv.questionbankentryid',
            'q.id as questionid',
            'q.*',
            'qc.id as category',
            DB::raw('COALESCE(qc.contextid, qsr.questionscontextid) as contextid'),
        ])
        ->leftJoin('mdl_question_references as qr', function ($join) use ($ctx) {
            $join->on('qr.itemid', '=', 'slot.id')
                ->where('qr.usingcontextid', '=', $ctx->id)
                ->where('qr.component', '=', 'mod_quiz')
                ->where('qr.questionarea', '=', 'slot');
        })
        ->leftJoin('mdl_question_bank_entries as qbe', 'qbe.id', '=', 'qr.questionbankentryid')
        ->leftJoin(DB::raw("
            (
                SELECT 
                    lv.questionbankentryid,
                    MAX(CASE WHEN lv.status <> 'draft' THEN lv.version END) AS usableversion,
                    MAX(lv.version) AS anyversion
                FROM mdl_quiz_slots lslot
                JOIN mdl_question_references lqr 
                    ON lqr.usingcontextid = {$ctx->id} 
                    AND lqr.component = 'mod_quiz'
                    AND lqr.questionarea = 'slot'
                    AND lqr.itemid = lslot.id
                JOIN mdl_question_versions lv 
                    ON lv.questionbankentryid = lqr.questionbankentryid
                WHERE lslot.quizid = {$quiz->id}
                AND lqr.version IS NULL
                GROUP BY lv.questionbankentryid
            ) as latestversions
        "), 'latestversions.questionbankentryid', '=', 'qr.questionbankentryid')
        ->leftJoin('mdl_question_versions as qv', function ($join) {
            $join->on('qv.questionbankentryid', '=', 'qbe.id')
                ->whereRaw('qv.version = COALESCE(qr.version, latestversions.usableversion, latestversions.anyversion)');
        })
        ->leftJoin('mdl_question_categories as qc', 'qc.id', '=', 'qbe.questioncategoryid')
        ->leftJoin('mdl_question as q', 'q.id', '=', 'qv.questionid')
        ->leftJoin('mdl_question_set_references as qsr', function ($join) use ($ctx) {
            $join->on('qsr.itemid', '=', 'slot.id')
                ->where('qsr.usingcontextid', '=', $ctx->id)
                ->where('qsr.component', '=', 'mod_quiz')
                ->where('qsr.questionarea', '=', 'slot');
        })
        ->where('slot.quizid', '=', $quiz->id)
        ->orderBy('slot.slot')
        ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $questions
        ]);

    }

    public function deleteQuestionFromQuiz($shortname, Quiz $quiz, $slotid){

        $quizSlot = QuizSlot::where('quizid', $quiz->id)
                            ->where('id', $slotid)
                            ->firstOrFail();

        // 2. Check if there are any non-preview attempts
        $hasAttempts = QuizAttempt::where('quiz', $quiz->id)
                                    ->where('preview', 0)
                                    ->exists();

        if ($hasAttempts) {
            throw new \Exception('Cannot delete question from quiz with existing attempts');
        }

        $qr = QuestionRefenrence::where('itemid', $slotid)
        ->where('component', 'mod_quiz')
        ->where('questionarea', 'slot')
        ->first();

        $currSlot = $quizSlot->slot;

        DB::connection('moodle_mysql')->beginTransaction();

        try {
            $qr->delete();
            $quizSlot->delete();

            $qs = QuizSlot::where('quizid', $quiz->id)
            ->orderBy('slot')
            ->orderBy('page')
            ->get();

            foreach ($qs as $slot) {
                if($slot->slot > $currSlot){
                    $slot->update([
                        'slot' => $slot->slot - 1,
                        'page' => $slot->page - 1,
                    ]);
                }
            }

            $quiz->update([
                'sumgrades' => DB::raw("COALESCE((SELECT SUM(maxmark) FROM mdl_quiz_slots WHERE quizid = mdl_quiz.id), 0)")
            ]);

            DB::connection('moodle_mysql')->commit();
        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function storeQuestionAndInsertToQuiz(Request $request, $shortname, Quiz $quiz){

        $course = Course::where('shortname', $shortname)->first();

        $ctx = Context::where('instanceid', $course->id)->where('contextlevel', 50)->first();

        $categories = DB::connection('moodle_mysql')->table('mdl_question_categories')
        ->where('contextid', $ctx->id)
        ->orderBy('id', 'DESC')
        ->get();

        DB::connection('moodle_mysql')->beginTransaction();

        $qtype = $request->type;

        if($request->type == 'multiple_choice'){
            $qtype = 'multichoice';
        } 

        if($request->type == 'true_false'){
            $qtype = 'truefalse';
        } 

        try {
            //code...
            $question = Question::create([
                'parent' => 0,
                'name' => $request->name,
                'questiontext' => $request->description,
                'questiontextformat' => 1,
                'qtype' => $qtype,
                'stamp' => 'localhost:8888+241224110017+FsP2Eu',
                'generalfeedback' => '',
                'generalfeedbackformat' => 1,
                'timecreated' => now()->timestamp,
                'timemodified' => now()->timestamp,
                'createdby' => $request->user()->id,
                'modifiedby' => $request->user()->id,
            ]);
    
            $qbe = QuestionBankEntry::create([
                'questioncategoryid' => $categories[0]->id,
                'idnumber' => null,
                'ownerid' => $request->user()->id,
            ]);
    
            $qv = QuestionVersion::create([
                'questionbankentryid' => $qbe->id,
                'questionid' => $question->id,
                'version' => 1,
                'status' => 'ready',
            ]);
    
            if($qtype == 'multichoice'){
                $options = $request->questionConfig['options'];
                foreach ($options as $key => $value) {
                    $correct = 0;
                    if($value['isRight']){
                        $correct = 1;
                    }
                    QuestionAnswer::create([
                        'question' => $question->id,
                        'answer' => $value['value'],
                        'fraction' => $correct,
                        'feedback' => '',
                        'feedbackformat' => 1,
                    ]);
                }
    
                QtypeMultiChoiceOption::create([
                    'questionid' => $question->id,
                    'correctfeedback' => '<p>Jawaban anda benar</p>',
                    'partiallycorrectfeedback' => '<p>Jawaban anda benar sebagian</p>',
                    'incorrectfeedback' => '<p>Jawaban anda salah</p>',
                    'showstandardinstruction' => 0,
                    'single' => 1,
                    'answernumbering' => 'abc',
                    'shuffleanswers' => 1,
                    'correctfeedbackformat' => 1,
                    'partiallycorrectfeedbackformat' => 1,
                    'incorrectfeedbackformat' => 1,
                    'shownumcorrect' => 1,
                ]);
    
            }

            if($qtype == 'truefalse'){
                $trueAnswer = QuestionAnswer::create([
                    'question' => $question->id,
                    'answer' =>  'True',
                    'fraction' => $request->questionConfig['true']['isRight'] ? 1 : 0,
                    'feedback' => '',
                    'feedbackformat' => 1,
                ]);

                $falseAnswer = QuestionAnswer::create([
                    'question' => $question->id,
                    'answer' =>  'False',
                    'fraction' => $request->questionConfig['false']['isRight'] ? 1 : 0,
                    'feedback' => '',
                    'feedbackformat' => 1,
                ]);

                QuestionTrueFalse::create([
                    'question' => $question->id,
                    'trueanswer' => $trueAnswer->id,
                    'falseanswer' => $falseAnswer->id,
                    'showstandardinstruction' => 0
                ]);

            }

            if($qtype == 'essay'){
                QtypeEssaiOption::create([
                    'questionid' => $question->id,
                    'responseformat' => 'editor',
                    'responserequired' => 1,
                    'responsefieldlines' => 10,
                    'minwordlimit' => null,
                    'maxwordlimit' => null,
                    'attachments' => 0,
                    'attachmentsrequired' => 0,
                    'filetypeslist' => '',
                    'maxbytes' => 0,
                    'graderinfo' => '',
                    'graderinfoformat' => 1,
                    'responsetemplate' => '',
                    'responsetemplateformat' => 1,
                ]);
            }

            $module = Module::where('name', 'quiz')->first();

            $cm = CourseModule::where('course', $course->id)
            ->where('module', $module->id)
            ->where('instance', $quiz->id)
            ->first();       

            $module_ctx = Context::where('instanceid', $cm->id)->where('contextlevel', 70)->first();

            $oldqs = QuizSlot::where('quizid', $quiz->id)->orderBy('slot', 'desc')->first();

            $qs = QuizSlot::create([
                'quizid' => $quiz->id,
                'maxmark' => 1,
                'slot' => $oldqs ? $oldqs->slot + 1 : 1,
                'page' => $oldqs ? $oldqs->page + 1 : 1,
            ]);
    
            QuestionRefenrence::create([
                'usingcontextid' => $module_ctx->id,
                'component' => 'mod_quiz',
                'questionarea' => 'slot',
                'itemid' => $qs->id,
                'questionbankentryid' => $qbe->id,
                'version' => null,
            ]);

            DB::connection('moodle_mysql')->commit();

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }


    }

    public function studentAttemptingQuiz(Request $request, Quiz $quiz){

        DB::connection('moodle_mysql')->beginTransaction();

        $cm = CourseModule::where('instance', $quiz->id)
        ->where('module', Module::where('name', 'quiz')->first()->id)
        ->where('course', $quiz->course)
        ->first();

        $ctx = Context::where('instanceid', $cm->id)
        ->where('contextlevel', 70)
        ->first();

        $questions = DB::connection('moodle_mysql')->table('mdl_quiz_slots as slot')
        ->select([
            'slot.slot',
            'slot.id as slotid',
            'slot.page',
            'slot.maxmark',
            'slot.displaynumber',
            'slot.requireprevious',
            'qsr.filtercondition',
            'qv.status',
            'qv.id as versionid',
            'qv.version',
            'qr.version as requestedversion',
            'qv.questionbankentryid',
            'q.id as questionid',
            'q.*',
            'qc.id as category',
            DB::raw('COALESCE(qc.contextid, qsr.questionscontextid) as contextid'),
        ])
        ->leftJoin('mdl_question_references as qr', function ($join) use ($ctx) {
            $join->on('qr.itemid', '=', 'slot.id')
                ->where('qr.usingcontextid', '=', $ctx->id)
                ->where('qr.component', '=', 'mod_quiz')
                ->where('qr.questionarea', '=', 'slot');
        })
        ->leftJoin('mdl_question_bank_entries as qbe', 'qbe.id', '=', 'qr.questionbankentryid')
        ->leftJoin(DB::raw("
            (
                SELECT 
                    lv.questionbankentryid,
                    MAX(CASE WHEN lv.status <> 'draft' THEN lv.version END) AS usableversion,
                    MAX(lv.version) AS anyversion
                FROM mdl_quiz_slots lslot
                JOIN mdl_question_references lqr 
                    ON lqr.usingcontextid = {$ctx->id} 
                    AND lqr.component = 'mod_quiz'
                    AND lqr.questionarea = 'slot'
                    AND lqr.itemid = lslot.id
                JOIN mdl_question_versions lv 
                    ON lv.questionbankentryid = lqr.questionbankentryid
                WHERE lslot.quizid = {$quiz->id}
                AND lqr.version IS NULL
                GROUP BY lv.questionbankentryid
            ) as latestversions
        "), 'latestversions.questionbankentryid', '=', 'qr.questionbankentryid')
        ->leftJoin('mdl_question_versions as qv', function ($join) {
            $join->on('qv.questionbankentryid', '=', 'qbe.id')
                ->whereRaw('qv.version = COALESCE(qr.version, latestversions.usableversion, latestversions.anyversion)');
        })
        ->leftJoin('mdl_question_categories as qc', 'qc.id', '=', 'qbe.questioncategoryid')
        ->leftJoin('mdl_question as q', 'q.id', '=', 'qv.questionid')
        ->leftJoin('mdl_question_set_references as qsr', function ($join) use ($ctx) {
            $join->on('qsr.itemid', '=', 'slot.id')
                ->where('qsr.usingcontextid', '=', $ctx->id)
                ->where('qsr.component', '=', 'mod_quiz')
                ->where('qsr.questionarea', '=', 'slot');
        })
        ->where('slot.quizid', '=', $quiz->id)
        ->orderBy('slot.slot')
        ->get();

        try {

            $quizAttempt = DB::connection('moodle_mysql')->table('mdl_quiz_attempts')
            ->where('quiz', $quiz->id)
            ->where('userid', $request->user()->id)
            ->first();

            if(!$quizAttempt){

                Log::info("sfs");

                $questionUsage = DB::connection('moodle_mysql')->table('mdl_question_usages')
                ->insertGetId([
                    'contextid' => $ctx->id,
                    'component' => 'mod_quiz',
                    'preferredbehaviour' => 'deferredfeedback',
                ]);

                $data_question_attempt_step_data = [];

                foreach($questions->toArray() as $i => $question){

                    $answers = QuestionAnswer::where('question', $question->id)
                    ->get();

                    $questionAppend = $answers->map(fn($item, $i) => ($i == 0 ? ': ' : '; ') . $item->answer)->implode("\n");

                    $trueAnswer = $answers->firstWhere('fraction', 1);

                    $questionAttempt = DB::connection('moodle_mysql')->table('mdl_question_attempts')
                    ->insertGetId([
                        'questionusageid' => $questionUsage,
                        'slot' => $question->slot,
                        'behaviour' => $question->qtype == 'essay' ? 'manualgraded' : 'deferredfeedback',
                        'questionid' => $question->questionid,
                        'variant' => 1,
                        'maxmark' => 1,
                        'minfraction' => 0,
                        'maxfraction' => 1,
                        'flagged' => 0,
                        'questionsummary' => strip_tags($question->questiontext."\r\n".$questionAppend)."\n",
                        'rightanswer' => $question->qtype == 'essay' ? null : strip_tags($trueAnswer->answer)."\n",
                        'responsesummary' => '',
                        'timemodified' => now()->timestamp,
                    ]);

                    $attempStepId = DB::connection('moodle_mysql')->table('mdl_question_attempt_steps')
                    ->insertGetId([
                        'questionattemptid' => $questionAttempt,
                        'sequencenumber' => 0,
                        'state' => 'todo',
                        'fraction' => null,
                        'timecreated' => now()->timestamp,
                        'userid' => $request->user()->id,
                    ]);

                    if($question->qtype != 'essay'){
                        $data_question_attempt_step_data[] = [
                            'attemptstepid' => $attempStepId,
                            'name' => '_order',
                            'value' => $answers->pluck('id')->implode(','),
                        ];
                    }

                }

                DB::connection('moodle_mysql')->table('mdl_question_attempt_step_data')
                ->insert($data_question_attempt_step_data);

                $layout = [];
                $count = 0;

                foreach($questions as $question){
                    $layout[] = $question->slot; // Tambahkan pertanyaan
                    $count++;

                    // Tambahkan "0" setelah setiap $questionsperpage
                    if ($count === $quiz->questionsperpage) {
                        $layout[] = 0;
                        $count = 0;
                    }
                }

                $newAttemptId = DB::connection('moodle_mysql')->table('mdl_quiz_attempts')
                ->insertGetId([
                    'quiz' => $quiz->id,
                    'userid' => $request->user()->id,
                    'attempt' => 1,
                    'uniqueid' => $questionUsage,
                    'layout' => implode(',', $layout),
                    'currentpage' => 0,
                    'preview' => 0,
                    'state' => 'inprogress',
                    'timestart' => now()->timestamp,
                    'timefinish' => 0,
                    'timemodified' => now()->timestamp,
                    'timemodifiedoffline' => 0,
                    // 'timecheckstate' => null,
                    // 'sumgrades' => null,
                ]);
            } else {
                $questionUsage = QuestionUsage::where('id', $quizAttempt->uniqueid)->first();   
            }

            DB::connection('moodle_mysql')->commit();

            $questions = DB::connection('moodle_mysql')->table('mdl_quiz_slots as qs')
            ->select([
                'qs.slot as question_order',
                'qs.page as question_page',
                'qs.maxmark as max_mark',
                'q.id as question_id',
                'q.name as question_name',
                'q.questiontext as question_text',
                'q.qtype as question_type',
                'qc.name as category_name',
                'qc.contextid as category_context_id',
            ])
            ->join('mdl_question_references as qr', function ($join) {
                $join->on('qr.itemid', '=', 'qs.id')
                    ->where('qr.component', '=', 'mod_quiz')
                    ->where('qr.questionarea', '=', 'slot');
            })
            ->join('mdl_question_bank_entries as qbe', 'qbe.id', '=', 'qr.questionbankentryid')
            ->join('mdl_question_versions as qv', function ($join) {
                $join->on('qv.questionbankentryid', '=', 'qbe.id')
                    ->whereRaw('qv.version = COALESCE(qr.version, (SELECT MAX(version) FROM mdl_question_versions WHERE questionbankentryid = qbe.id))');
            })
            ->join('mdl_question as q', 'q.id', '=', 'qv.questionid')
            ->join('mdl_question_categories as qc', 'qc.id', '=', 'qbe.questioncategoryid')
            ->where('qs.quizid', '=', $quiz->id)
            ->orderBy('qs.slot')
            ->get();

            $questionState = DB::connection('moodle_mysql')->table('mdl_question_usages as quba')
            ->select(
                'quba.id as qubaid',
                'quba.contextid',
                'quba.component',
                'quba.preferredbehaviour',
                'qa.id as questionattemptid',
                'qa.questionusageid',
                'qa.slot',
                'qa.behaviour',
                'qa.questionid',
                'qa.variant',
                'qa.maxmark',
                'qa.minfraction',
                'qa.maxfraction',
                'qa.flagged',
                'qa.questionsummary',
                'qa.rightanswer',
                'qa.responsesummary',
                'qa.timemodified',
                'qas.id as attemptstepid',
                'qas.sequencenumber',
                'qas.state',
                'qas.fraction',
                'qas.timecreated',
                'qas.userid',
                'qasd.name',
                'qasd.value'
            )
            ->leftJoin('mdl_question_attempts as qa', 'qa.questionusageid', '=', 'quba.id')
            ->leftJoin('mdl_question_attempt_steps as qas', 'qas.questionattemptid', '=', 'qa.id')
            ->leftJoin('mdl_question_attempt_step_data as qasd', 'qasd.attemptstepid', '=', 'qas.id')
            ->where('quba.id', '=', $questionUsage->id)
            ->orderBy('qa.slot')
            ->orderBy('qas.sequencenumber')
            ->get();

            foreach($questions as $question){

                $question->last_state = $questionState->filter(fn($qs) => $qs->questionid == $question->question_id)
                ->sortByDesc('sequencenumber')
                ->select(
                    'state','name','value','qubaid'
                )
                ->first();

                $question->answers = QuestionAnswer::where('question', $question->question_id)->get()
                ->map(function($item){
                    return [
                        'answer' => $item->answer,
                        'fraction' => $item->fraction,
                        'feedback' => $item->feedback,
                        'feedbackformat' => $item->feedbackformat,
                    ];
                });
            }

            $quizInstance = new \stdClass();

            $quizInstance->id = $quiz->id;
            $quizInstance->name = $quiz->name;
            $quizInstance->questionsperpage = $quiz->questionsperpage;
            $quizInstance->current_attempt_id = $quizAttempt ? $quizAttempt->id : $newAttemptId;
            $quizInstance->question_usage_id = $questionUsage->id;
            $currentTime = Carbon::now()->timestamp;
            $quizEndTime = $quizAttempt->timestart + $quiz->timelimit;
            $quizInstance->duration = $quiz->timelimit == 0 
                ? 0 
                : max(0, $quizEndTime - $currentTime);

            return response()->json([
                'message' => 'Success',
                'data' => [
                    'quiz' => $quizInstance,
                    'questions' => $questions
                ]
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            throw $th;
        }

    }

    public function saveStudentState(Request $request){
        try {
            DB::connection('moodle_mysql')->beginTransaction();

            foreach($request->questions as $question){
    
                $questionAttempt = QuestionAttempt::where('questionusageid', $question['last_state']['qubaid'])
                ->where('questionid', $question['question_id'])
                ->first();

                $questionAttemptStepSequence = QuestionAttemptStep::where('questionattemptid', $questionAttempt->id)
                ->max('sequencenumber');

                $newQuestionAttemptStepId =  DB::connection('moodle_mysql')->table('mdl_question_attempt_steps')
                ->insertGetId([
                    'questionattemptid' => $questionAttempt->id,
                    'sequencenumber' => $questionAttemptStepSequence + 1,
                    'state' => $question['last_state']['state'],
                    'userid' => $request->user()->id,
                    'timecreated' => now()->timestamp,
                ]);

                DB::connection('moodle_mysql')->table('mdl_question_attempt_step_data')
                ->insert([
                    'attemptstepid' => $newQuestionAttemptStepId,
                    'name' => 'answer',
                    'value' => $question['last_state']['value'],
                ]);

                if($question['question_type'] == 'essay'){
                    DB::connection('moodle_mysql')->table('mdl_question_attempt_step_data')
                    ->insert([
                        'attemptstepid' => $newQuestionAttemptStepId,
                        'name' => 'answerformat',
                        'value' => '1',
                    ]);
                } 
            }

            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'Success save student state',
                'data' => null
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function finishStudentAttempt(Request $request, Quiz $quiz){

        $there_is_essay = false;

        try {
            DB::connection('moodle_mysql')->beginTransaction();

            $questionState = DB::connection('moodle_mysql')->table('mdl_question_usages as quba')
            ->select(
                'quba.id as qubaid',
                'quba.contextid',
                'quba.component',
                'quba.preferredbehaviour',
                'qa.id as questionattemptid',
                'qa.questionusageid',
                'qa.slot',
                'qa.behaviour',
                'qa.questionid',
                'qa.variant',
                'qa.maxmark',
                'qa.minfraction',
                'qa.maxfraction',
                'qa.flagged',
                'qa.questionsummary',
                'qa.rightanswer',
                'qa.responsesummary',
                'qa.timemodified',
                'qas.id as attemptstepid',
                'qas.sequencenumber',
                'qas.state',
                'qas.fraction',
                'qas.timecreated',
                'qas.userid',
                'qasd.name',
                'qasd.value'
            )
            ->leftJoin('mdl_question_attempts as qa', 'qa.questionusageid', '=', 'quba.id')
            ->leftJoin('mdl_question_attempt_steps as qas', 'qas.questionattemptid', '=', 'qa.id')
            ->leftJoin('mdl_question_attempt_step_data as qasd', 'qasd.attemptstepid', '=', 'qas.id')
            ->where('quba.id', '=', $request->quiz['question_usage_id'])
            ->orderBy('qa.slot')
            ->orderBy('qas.sequencenumber')
            ->get();

            $question_attempt_steps_id = [];

            foreach($request->questions as $question){

                $qs = $questionState->filter(fn($qs) => $qs->questionid == $question['question_id'])
                ->where('name', 'answer')
                ->sortByDesc('sequencenumber')
                ->first();

                if($qs->behaviour == 'manualgraded'){
                    $state = 'needsgrading';
                    $there_is_essay = true;
                }

                if($qs->behaviour == 'deferredfeedback'){

                    $optionsString = substr($qs->questionsummary, strpos($qs->questionsummary, ':') + 1);
                    $options = array_map('trim', explode(';', str_replace(["\r", "\n"], '', $optionsString)));

                    $userAnswer = $options[$qs->value] ?? null; 

                    if($userAnswer == str_replace(["\r", "\n"], '', $qs->rightanswer)){
                        $state = 'gradedright';
                    } else {
                        $state = 'gradedwrong';
                    }

                }

                $question_attempt_steps_id[] = DB::connection('moodle_mysql')->table('mdl_question_attempt_steps')
                ->insertGetId([
                    'questionattemptid' => $qs->questionattemptid,
                    'sequencenumber' => $qs->sequencenumber + 1,
                    'state' => $state,
                    'fraction' => $state == 'needsgrading' ? null : ($state == 'gradedright' ? 1 : 0),
                    'timecreated' => now()->timestamp,
                    'userid' => $request->user()->id,
                ]);

            }

            foreach($questionState as $qs){
                DB::connection('moodle_mysql')->table('mdl_question_attempts')
                ->where('id', $qs->questionattemptid)
                ->update([
                    'timemodified' => now()->timestamp,
                ]);
            }

            $questionAttemptStepsData = collect($question_attempt_steps_id)
            ->map(function($item){
                return [
                    'attemptstepid' => $item,
                    'name' => '-finish',
                    'value' => 1,
                ];
            });

            DB::connection('moodle_mysql')->table('mdl_question_attempt_step_data')
            ->insert($questionAttemptStepsData->toArray());

            DB::connection('moodle_mysql')->table('mdl_quiz_attempts')
            ->where('id', $request->quiz['current_attempt_id'])
            ->update([
                'state' => 'finished',
                'timefinish' => now()->timestamp,
                'timemodified' => now()->timestamp,
            ]);


            if(!$there_is_essay){

                $attemptSteps = DB::connection('moodle_mysql')
                ->table('mdl_question_attempts as qa')
                ->join('mdl_question_attempt_steps as qas', function($join) {
                    $join->on('qas.questionattemptid', '=', 'qa.id')
                        ->whereRaw('qas.sequencenumber = (
                            SELECT MAX(sequencenumber) 
                            FROM mdl_question_attempt_steps 
                            WHERE questionattemptid = qa.id
                        )');
                })
                ->where('qa.questionusageid', $request->quiz['question_usage_id'])
                ->select([
                    'qa.slot',
                    'qa.maxmark',
                    'qas.fraction',
                ])
                ->get();

                $sumgrades = $attemptSteps->sum('fraction');

                DB::connection('moodle_mysql')->table('mdl_quiz_attempts')
                ->where('id', $request->quiz['current_attempt_id'])
                ->update([
                    'timemodified' => now()->timestamp,
                    'sumgrades' => $sumgrades
                ]);

                $grade = $this->quizGraderHelper->calculateQuizGrade(
                    $quiz->id,
                    $request->quiz['question_usage_id']
                );
    
                $this->quizGraderHelper->saveQuizGrade(
                    $quiz->id,
                    $request->user()->id,
                    $grade['final_grade']
                );
            }


            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'Success finish student attempt',
                'data' => null
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            throw $th;
        }
    }

    public function getStudentAnswerStateData(Request $request, Quiz $quiz){

        $question_attempt = QuizAttempt::where('quiz', $quiz->id)
        ->where('userid', $request->user()->id)
        ->first();
        
        $data = DB::connection('moodle_mysql')->table('mdl_question_usages AS quba')
        ->leftJoin('mdl_question_attempts AS qa', 'qa.questionusageid', '=', 'quba.id')
        ->leftJoin('mdl_question_attempt_steps AS qas', 'qas.questionattemptid', '=', 'qa.id')
        ->leftJoin('mdl_question_attempt_step_data AS qasd', 'qasd.attemptstepid', '=', 'qas.id')
        ->select(
            'quba.id AS qubaid',
            'quba.contextid',
            'quba.component',
            'quba.preferredbehaviour',
            'qa.id AS questionattemptid',
            'qa.questionusageid',
            'qa.slot',
            'qa.behaviour',
            'qa.questionid',
            'qa.variant',
            'qa.maxmark',
            'qa.minfraction',
            'qa.maxfraction',
            'qa.flagged',
            'qa.questionsummary',
            'qa.rightanswer',
            'qa.responsesummary',
            'qa.timemodified',
            'qas.id AS attemptstepid',
            'qas.sequencenumber',
            'qas.state',
            'qas.fraction',
            'qas.timecreated',
            'qas.userid',
            'qasd.name',
            'qasd.value'
        )
        ->where('quba.id', '=', $question_attempt->uniqueid)
        ->orderBy('qa.slot')
        ->orderBy('qas.sequencenumber')
        ->get();
        
        return response()->json([
            'message' => 'get student answer state data success',
            'data' => $data
        ]);
    }

    public function gradeEssay(Request $request, Quiz $quiz, $usageid){

        DB::connection('moodle_mysql')->beginTransaction();

        try {

            foreach($request->questions ?? [] as $attemptid => $questionMark){

                $questionAttemptStepSequence = QuestionAttemptStep::where('questionattemptid', $attemptid)
                ->max('sequencenumber');   
    
                $newQuestionAttemptStepId =  DB::connection('moodle_mysql')->table('mdl_question_attempt_steps')
                ->insertGetId([
                    'questionattemptid' => $attemptid,
                    'sequencenumber' => $questionAttemptStepSequence + 1,
                    'state' => 'mangrright',
                    'fraction' => $questionMark * 0.1,
                    'timecreated' => now()->timestamp,
                    'userid' => $request->user()->id,
                ]);
    
                DB::connection('moodle_mysql')->table('mdl_question_attempt_step_data')
                ->insert(
                    [
                        'attemptstepid' => $newQuestionAttemptStepId,
                        'name' => '-comment',
                        'value' => '',
                    ],
                    [
                        'attemptstepid' => $newQuestionAttemptStepId,
                        'name' => '-commentformat',
                        'value' => '1',
                    ],
                    [
                        'attemptstepid' => $newQuestionAttemptStepId,
                        'name' => '-mark',
                        'value' => $questionMark * 0.1 ,
                    ],
                    [
                        'attemptstepid' => $newQuestionAttemptStepId,
                        'name' => '-maxmark',
                        'value' => 1,
                    ],
                );
    
            }

            $essaysNotGraded = DB::connection('moodle_mysql')->table('mdl_question_attempts as qa')
            ->join('mdl_question as q', 'q.id', '=', 'qa.questionid')
            ->join('mdl_question_attempt_steps as qas', 'qas.questionattemptid', '=', 'qa.id')
            ->where('q.qtype', 'essay') // Hanya untuk pertanyaan esai
            ->where('qas.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('mdl_question_attempt_steps as sub_qas')
                    ->whereColumn('sub_qas.questionattemptid', 'qa.id'); // Ambil langkah terakhir
            })
            ->where('qas.state', '!=', 'mangrright') // Langkah terakhir bukan status dinilai
            ->where('qa.questionusageid', $usageid) // Ganti dengan ID penggunaan kuis
            ->select([
                'qa.id as question_attempt_id',
                'qa.questionid',
                'q.name as question_name',
                'qa.maxmark',
                'qas.sequencenumber',
                'qas.state',
            ])
            ->exists();

            if(!$essaysNotGraded){

                $grade = $this->quizGraderHelper->calculateQuizGrade(
                    $quiz->id,
                    $usageid
                );
    
                $this->quizGraderHelper->saveQuizGrade(
                    $quiz->id,
                    $request->userid,
                    $grade['final_grade']
                );

                $attemptSteps = DB::connection('moodle_mysql')
                ->table('mdl_question_attempts as qa')
                ->join('mdl_question_attempt_steps as qas', function($join) {
                    $join->on('qas.questionattemptid', '=', 'qa.id')
                        ->whereRaw('qas.sequencenumber = (
                            SELECT MAX(sequencenumber) 
                            FROM mdl_question_attempt_steps 
                            WHERE questionattemptid = qa.id
                        )');
                })
                ->where('qa.questionusageid', $usageid)
                ->select([
                    'qa.slot',
                    'qa.maxmark',
                    'qas.fraction',
                ])
                ->get();

                $sumgrades = $attemptSteps->sum('fraction');

                DB::connection('moodle_mysql')->table('mdl_quiz_attempts')
                ->where([
                    'userid' => $request->userid,
                    'quiz' => $quiz->id,
                    'uniqueid' => $usageid
                ])
                ->update([
                    'timemodified' => now()->timestamp,
                    'sumgrades' => $sumgrades
                ]);
    
                $gradeItem = DB::connection('moodle_mysql')->table('mdl_grade_items')
                ->where('courseid', $quiz->course)
                ->where('iteminstance', $quiz->id)
                ->where('itemnumber', 0)
                ->where('itemtype', 'mod')
                ->where('itemmodule', 'quiz')
                ->first();
    
                $this->gradeHelper->updateStudentGrade(
                    $gradeItem->id,
                    $request->userid,
                    $grade['raw_grade'],
                    $grade['final_grade'],
                    $request->user()->id
                );
        
                $gradeCategory = DB::connection('moodle_mysql')->table('mdl_grade_categories')
                ->where('courseid', $quiz->course)
                ->first();
        
                $calculableItems = $this->gradeHelper->getCalculableGradeItems(
                    $quiz->course,
                    $gradeCategory->id
                );
                
                $this->gradeHelper->updateAggregationWeights(
                    $quiz->course,
                    $request->userid,
                    $calculableItems->pluck('id')->toArray()
                );
            }


            DB::connection('moodle_mysql')->commit();

            return response()->json([
                'message' => 'success grading essay'
            ]);

        } catch (\Throwable $th) {
            DB::connection('moodle_mysql')->rollBack();
            throw $th;
        }


    }


}
