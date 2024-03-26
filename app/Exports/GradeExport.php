<?php

namespace App\Exports;

use App\Models\Context;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GradeExport implements WithMultipleSheets
{
    /**
    * @return \Illuminate\Support\Collection
    */

    use Exportable;

    protected Course $course;

    protected $quiz;

    protected $assignment;

    protected $type;

    public function __construct(Course $course, $quiz, $assignment, $type = 'all')
    {
        $this->course = $course;
        $this->quiz = $quiz;
        $this->assignment = $assignment;
        $this->type = $type;
    }

    public function sheets(): array
    {

        $context = Context::where('instanceid', $this->course->id)
        ->where('contextlevel', 50)
        ->first();

        $gradesStudent = DB::connection('moodle_mysql')
        ->table('mdl_role_assignments as ra')
        ->where('contextid', $context->id)
        ->where('r.shortname', '!=', 'editingteacher')
        ->join('mdl_user as u', 'u.id', '=', 'ra.userid')
        ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
        ->select(
            'u.id',
            DB::raw("CONCAT(u.firstname,' ',u.lastname) as fullname"),
            'u.username as nim',
        )->get();

        $sheets = [];

        if($this->type == 'all' || $this->type == 'assignment'){
            $sheets[] = new AssignmentExport($gradesStudent, $this->assignment);
        }

        if($this->type == 'all' || $this->type == 'quiz'){
            $sheets[] = new QuizExport($gradesStudent, $this->quiz);
        }

        return $sheets;
    }

}
