<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
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
use App\Models\QuestionBankEntry;
use App\Models\QuestionRefenrence;
use App\Models\QuestionTrueFalse;
use App\Models\QuestionVersion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSection;
use App\Models\QuizSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{

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
                'questionsperpage' => 1, // Jumlah pertanyaan per halaman
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

}
