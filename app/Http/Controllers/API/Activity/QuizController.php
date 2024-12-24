<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\Course;
use App\Models\Event;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\GradeItemHistory;
use App\Models\Module;
use App\Models\QtypeMultiChoiceOption;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuestionBankEntry;
use App\Models\QuestionVersion;
use App\Models\Quiz;
use App\Models\QuizSection;
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

    public function getBankQuestion($shortname){

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
        ->get();

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

        if($request->type == 'multiple_choice'){
            $qtype = 'multichoice';
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
                QuestionAnswer::where('question', $question->id)->delete();
                DB::connection('moodle_mysql')->table('mdl_question_hints')->where('questionid', $question->id)->delete();
                DB::connection('moodle_mysql')->table('mdl_question_hints')->where('questionid', $question->id)->delete();
                $qv = QuestionVersion::where('questionid', $question->id)->get();
                foreach ($qv as $qvv) {
                    QuestionBankEntry::where('id', $qvv->questionbankentryid)->delete();
                    $qvv->delete();
                }
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

        if($question->qtype == 'multichoice'){
            $instance->type = 'multiple_choice';
        }

        if($question->qtype == 'multichoice'){
            $options = QuestionAnswer::where('question', $question->id)->get();
            $instance->questionConfig = [
                'options' => $options->map(function($item){
                    return [
                        'value' => $item->answer,
                        'isRight' => $item->fraction == 1 ? true : false
                    ];
                })->toArray(),
                'numberOfOptions' => $options->count()
            ];
        }

        return response()->json([
            'message' => 'Success',
            'data' => $instance
        ], 200);

    }

    public function updateQuestion(Request $request, $shortname, Question $question){

        $course = Course::where('shortname', $shortname)->first();

        $ctx = Context::where('instanceid', $course->id)->where('contextlevel', 50)->first();

        $categories = DB::connection('moodle_mysql')->table('mdl_question_categories')
        ->where('contextid', $ctx->id)
        ->orderBy('id', 'DESC')
        ->get();

        $oldQv = QuestionVersion::where('questionid', $question->id)
            ->orderBy('version', 'desc')
            ->first();

        DB::connection('moodle_mysql')->beginTransaction();

        if($request->type == 'multiple_choice'){
            $qtype = 'multichoice';
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
