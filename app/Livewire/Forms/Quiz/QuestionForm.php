<?php

namespace App\Livewire\Forms\Quiz;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\StudentQuiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class QuestionForm extends Form
{
    public Quiz $quiz;

    public $questions = [];

    public function store(){

        $questionIds = [];
        
        // Log::info(json_decode($this->questions));
        // return;

        DB::beginTransaction();

        try {
            foreach($this->questions as $question){
                $instance = Question::updateOrCreate(
                    [
                        'id' => $question['id'] ?? null
                    ],
                    [
                        'user_id' => auth()->user()->id,
                        'type' => $question['type'],
                        'question' => $question['question'],
                        'type' => $question['type'],
                        'point' => $question['point'] ?? 0,
                        'option' => count($question['answers']),
                    ]   
                );

                $questionIds[] = $instance->id;

                $instance->answers()->delete();
    
                foreach($question['answers'] as $answer){
                    $instance->answers()->create([
                        'answer' => $answer['answer'],
                        'is_true' => $answer['is_true'] ?? 0,
                    ]);
                }

            }

            $this->quiz->questions()->sync($questionIds);

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

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

    public function setModel(Quiz $quiz){
        $this->quiz = $quiz;
        $questions = $quiz->questions->map(function($e){
            return [
                'id' => $e->id,
                'question' => $e->question,
                'type' => $e->type,
                'point' => $e->point,
                'option' => $e->option,
                'answers' => $e->answers->map(function($a){
                    return [
                        'id' => $a->id,
                        'answer' => $a->answer,
                        'is_true' => $a->is_true,
                    ];
                })->toArray()
            ];
        });
        $this->questions = $questions->toArray();

    }

}
