<?php

namespace App\Livewire\Forms\Attendance;

use App\Models\Attendance;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FillForm extends Form
{
    public Attendance $attendance;

    public $students = [];

    public function setModel(Attendance $attendance){
        $this->attendance = $attendance;
        $studentAttendances = StudentAttendance::where('attendance_id', $attendance->id)->get();
        $studentAttendancesIds = $studentAttendances->pluck('student_id')->toArray();
        $students = User::whereIn('id', $studentAttendancesIds)->get();
        $this->students = $students->map(function ($e) use ($studentAttendances) {
            $att = $studentAttendances->firstWhere('student_id', $e->id);
            return [
                'id' => $e->id,
                'attendance_id' => $att->id,
                'name' => $e->firstname . ' ' . $e->lastname,
                'nim' => $e->username,
                'status' => $att->status,
                'note' => $att->note,
            ];
        });   
    }

    public function submit(){
        try {
            
            foreach($this->students as $s){
                StudentAttendance::where('id', $s['attendance_id'])->update([
                    'status' => $s['status'],
                    'note' => $s['note'],
                ]);
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
