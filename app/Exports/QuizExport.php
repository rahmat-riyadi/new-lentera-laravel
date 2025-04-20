<?php

namespace App\Exports;

use App\Models\StudentQuiz;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use stdClass;

class QuizExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithTitle, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $gradesStudent;
    protected $quiz;

    public function __construct($gradesStudent, $quiz)
    {
        $this->gradesStudent = $gradesStudent;
        $this->quiz = $quiz;
    }

    public function collection()
    {
        foreach($this->gradesStudent as $student){
            $student->quiz_grades = new stdClass();
            foreach($this->quiz->pluck('id') as $i => $qid){
                $quiz = StudentQuiz::where('quiz_id', $qid)
                ->where('student_id', $student->id)
                ->leftJoin('student_quiz_answers as sqa', 'sqa.student_quiz_id', '=', 'student_quizzes.id')
                ->select(
                    DB::raw('SUM(sqa.grade) as grade')
                )
                ->first();
                $student->quiz_grades->{"quiz_$i"} = new stdClass();
                $student->quiz_grades->{"quiz_$i"}->title = $this->quiz->first(fn($e) => $e->id == $qid)->name ?? null;
                $student->quiz_grades->{"quiz_$i"}->grade = $quiz->grade ?? 0.00;
    
            }

        }
        return $this->gradesStudent;
    }

    public function map($gradeData): array
    {

        $data = [
            $gradeData->fullname,
            $gradeData->nim,
        ];

        foreach($gradeData->quiz_grades ?? [] as $quiz){
            $data[] = $quiz->grade;
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {

        $sheet->getRowDimension(1)->setRowHeight(35);

        return [
            // Style the first row as bold text.
            1 => [
                'font' => [
                    'size' => 13
                ],
                'height' => 20,
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function headings(): array
    {

        $data = ['Mahasiswa', 'NIM'];

        foreach($this->quiz ?? [] as $quiz){
            $data[] = $quiz->name;
        }

        return $data;
    }

    public function title(): string
    {
        return 'Quiz';
    }

}
