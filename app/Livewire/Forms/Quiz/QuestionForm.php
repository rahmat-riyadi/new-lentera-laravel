<?php

namespace App\Livewire\Forms\Quiz;

use App\Models\Question;
use App\Models\Quiz;
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

        DB::beginTransaction();

        try {
            foreach($this->questions as $question){
                $instance = Question::create([
                    'user_id' => auth()->user()->id,
                    'type' => $question['type'],
                    'question' => $question['question'],
                    'type' => $question['type'],
                    'point' => $question['point'] ?? 0,
                    'option' => count($question['answers']),
                ]);

                $questionIds[] = $instance->id;
    
                foreach($question['answers'] as $answer){
                    $instance->answers()->create([
                        'answer' => $answer['answer'],
                        'is_true' => $answer['is_true'] ?? 0,
                    ]);
                }

            }

            $this->quiz->questions()->sync($questionIds);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

    public function setModel(Quiz $quiz){
        $this->quiz = $quiz;
    }

}
