<?php

namespace App\Livewire\Forms\Activity;

use App\Helpers\CourseHelper;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\StudentQuiz;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Rule;
use Livewire\Form;

class QuizForm extends Form
{
    function boot(){
        $this->module = Module::where('name', 'quiz')->first();
    }
    
    public Module $module;
    
    public ?Quiz $quiz;

    public ?Course $course;

    public $section_num;

    #[Rule('required', message: 'Judul Tugas harus diisi')]
    public $name;

    #[Rule('nullable', message: 'Deskripsi harus diisi')]
    public $description;

    #[Rule('required', message: 'Jenis waktu pengiriman harus diiisi')]
    public $due_date_type;

    #[Rule('nullable', message: 'Waktu mulai harus diiisi')]
    public $start_date;

    #[Rule('nullable', message: 'Waktu mulai harus diiisi')]
    public $start_time;

    #[Rule('required', message: 'Waktu berakhir harus diiisi')]
    public $due_date;

    #[Rule('nullable', message: 'Waktu berakhir harus diiisi')]
    public $due_time;

    #[Rule('nullable')]
    public $shuffle_questions;

    #[Rule('nullable')]
    public $question_show_number;

    #[Rule('nullable')]
    public $answer_attempt;

    #[Rule('nullable')]
    public $pass_grade;
    
    #[Rule('nullable')]
    public $show_grade;

    #[Rule('nullable')]
    public $show_answers;

    #[Rule('nullable')]
    public $activity_remember;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function setInstance(Quiz $quiz){

        $this->quiz = $quiz;

        $this->fill([
            'name' => $quiz->name,
            'description' => $quiz->description,
            'shuffle_questions' => $quiz->shuffle_questions,
            'question_show_number' => $quiz->question_show_number,
            'answer_attempt' => $quiz->answer_attempt,
            'pass_grade' => $quiz->pass_grade,
            'show_grade' => $quiz->show_grade,
            'show_answers' => $quiz->show_answers,
            'activity_remember' => $quiz->activity_remember,
        ]);

        $start_date = Carbon::parse($quiz->start_date);
        $end_date = Carbon::parse($quiz->due_date);

        $this->start_date = $start_date->format('Y-m-d');
        $this->due_date = $end_date->format('Y-m-d');

        $this->start_time = $start_date->format('H:i');
        $this->due_time = $end_date->format('H:i');

        if($start_date->diffInDays($end_date) == 0){
            $this->due_date_type = 'time';
        } else {
            $this->due_date_type = 'date';
        }

    }

    public function store(){

        $start_date = Carbon::parse($this->start_date . ' '. $this->start_time );
        $due_date = Carbon::parse($this->due_date . ' '. $this->due_time );

        DB::beginTransaction();

        try {
            
            $instance = $this->course->quiz()->create([
                'name' => $this->name,
                'description' => $this->description,
                'start_date' => $start_date,
                'due_date' => $due_date,
                'pass_grade' => $this->pass_grade,
                'answer_attempt' => $this->answer_attempt ?? 0,
                'show_grade' => $this->show_grade ?? 0,
                'show_answers' => $this->show_answers ?? 0,
                'shuffle_questions' => $this->shuffle_questions ?? 0,
                'question_show_number' => $this->question_show_number ?? 5,
                'activity_remember' => $this->activity_remember ?? null,
            ]);

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
                        'quiz_id' => $instance->id
                    ],
                    [
                        'student_id' => $participant->id,
                        'quiz_id' => $instance->id
                    ],
                );   
            }


            $cm = CourseHelper::addCourseModule($this->course->id, $this->module->id, $instance->id);
            CourseHelper::addContext($cm->id, $this->course->id);
            CourseHelper::addCourseModuleToSection($this->course->id, $cm->id, $this->section_num);

            DB::commit();
            
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

    public function update(){

        $start_date = Carbon::parse($this->start_date . ' '. $this->start_time );
        $due_date = Carbon::parse($this->due_date . ' '. $this->due_time );

        DB::beginTransaction();

        try {

            $this->quiz->update([
                'name' => $this->name,
                'description' => $this->description,
                'start_date' => $start_date,
                'due_date' => $due_date,
                'pass_grade' => $this->pass_grade ?? 100,
                'answer_attempt' => $this->answer_attempt ?? 0,
                'show_grade' => $this->show_grade ?? 0,
                'show_answers' => $this->show_answers ?? 0,
                'shuffle_questions' => $this->shuffle_questions ?? 0,
                'question_show_number' => $this->question_show_number ?? 5,
                'activity_remember' => $this->activity_remember ?? null,
            ]);

            if($this->quiz->questions->count() != 0){
                $questionIds = $this->quiz->questions->pluck('id')->toArray();
                $role = Role::where('shortname', 'student')->first();

                $participantsData = DB::connection('moodle_mysql')
                ->table('mdl_enrol')
                ->where('mdl_enrol.courseid', '=', $this->quiz->course_id)
                ->where('mdl_enrol.roleid', '=', $role->id)
                ->where('mdl_user_enrolments.userid', '!=', auth()->user()->id)
                ->join('mdl_user_enrolments', 'mdl_user_enrolments.enrolid', 'mdl_enrol.id')
                ->join('mdl_user', 'mdl_user.id', 'mdl_user_enrolments.userid')
                ->select('mdl_user.id')->get();

                foreach($participantsData as $i => $participant){

                    if($i != 0){
                        $questionIds = array_diff($questionIds, [0]);
                    }

                    shuffle($questionIds);

                    for ($j = $this->quiz->question_show_number; $j < count($questionIds); $j += $this->quiz->question_show_number+1) {
                        array_splice($questionIds, $j, 0, 0);
                    }

                    $data = [
                        'student_id' => $participant->id,
                        'layout' => json_encode($questionIds),
                        'quiz_id' => $this->quiz->id
                    ];

                    StudentQuiz::updateOrCreate(
                        [
                            'student_id' => $participant->id,
                            'quiz_id' => $this->quiz->id
                        ],
                        $data
                    );
                }
            }

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

}
