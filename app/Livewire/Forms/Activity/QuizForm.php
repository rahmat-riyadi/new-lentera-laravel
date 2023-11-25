<?php

namespace App\Livewire\Forms\Activity;

use App\Models\Course;
use Carbon\Carbon;
use Livewire\Attributes\Rule;
use Livewire\Form;

class QuizForm extends Form
{

    public ?Course $course;
    public $section_num;

    #[Rule('required', message: 'Atribut judul harus diisi')]
    public $name;
    
    #[Rule('required', message: 'Atribut deskripsi harus diisi')]
    public $intro;

    #[Rule('nullable')]
    public $grade;

    #[Rule('required')]
    public $duedatetype;

    public $timeopen_date;

    public $timeopen_time;

    public $timeopen;

    public $timeclose_date;

    public $timeclose_time;

    public $timeclose;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function store(){

        if($this->duedatetype == 'time'){
            $currDay = Carbon::now()->format('Y-m-d');
            $this->timeopen = "{$currDay} {$this->timeopen_time}";
            $this->timeopen = strtotime($this->timeopen);
        } else {
            $this->timeopen = "{$this->timeopen_date} {$this->timeopen_time}";
            $this->timeopen = strtotime($this->timeopen);
        }

        if($this->duedatetype == 'time'){
            $currDay = Carbon::now()->format('Y-m-d');
            $this->timeclose = "{$currDay} {$this->timeclose_time}";
            $this->timeclose = strtotime($this->timeclose);
        } else {
            $this->timeclose = "{$this->timeclose_date} {$this->timeclose_time}";
            $this->timeclose = strtotime($this->timeclose);
        }


        $this->course->quiz()->create([
            'name' => $this->name,
            'intro' => $this->intro,
            'introformat' => 1,
            'timeopen' => $this->timeopen,
            'timeclose' => $this->timeclose,
        ]);


    }

}
