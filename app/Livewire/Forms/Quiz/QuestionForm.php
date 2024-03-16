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
        Log::info(json_decode($questions));

    }

}
