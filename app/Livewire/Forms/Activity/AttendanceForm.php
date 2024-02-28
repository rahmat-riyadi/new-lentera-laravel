<?php

namespace App\Livewire\Forms\Activity;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Module;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AttendanceForm extends Form
{
    function boot(){
        $this->module = Module::where('name', 'attendance')->first();
    }

    public Module $module;

    public ?Attendance $attendance;

    public ?Course $course;

    public $section_num;

    #[Rule('required', message: 'Judul harus diisi')]
    public $name;
    
    #[Rule('required', message: 'Deskripsi harus diisi')]
    public $description;

    #[Rule('required', message: 'Tanggal harus diisi')]
    public $date;

    #[Rule('required', message: 'Waktu mulai harus diisi')]
    public $starttime;

    #[Rule('required', message: 'Waktu akhir harus diisi')]
    public $endtime;

    #[Rule('nullable')]
    public $is_repeat;

    #[Rule('required', message:'Pengisi kehadiran harus diisi')]
    public $filled_by;

    #[Rule('nullable')]
    public $repeat_attempt;

    public function setModel(Course $course){
        $this->course = $course;
    }

    public function setSection($section_num){
        $this->section_num = $section_num;
    }

    public function store(){
        try {
            Attendance::create(
                $this->only('name', 'description')
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
