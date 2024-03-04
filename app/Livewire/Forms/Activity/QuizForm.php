<?php

namespace App\Livewire\Forms\Activity;

use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
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

}
