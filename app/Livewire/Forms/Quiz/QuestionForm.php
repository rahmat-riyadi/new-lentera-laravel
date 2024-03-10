<?php

namespace App\Livewire\Forms\Quiz;

use App\Models\Quiz;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class QuestionForm extends Form
{
    public Quiz $quiz;

    public $questions = [];

    public function store(){
        Log::info($this->questions);
    }

}
