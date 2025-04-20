<?php

namespace App\Exports;

use App\Models\AssignmentSubmission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use stdClass;

class AssignmentExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithTitle, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $gradesStudent;
    protected $assignment;

    public function __construct($gradesStudent, $assignment)
    {
        $this->gradesStudent = $gradesStudent;
        $this->assignment = $assignment;
    }


    public function collection()
    {
        foreach($this->gradesStudent as $student){
            $student->assignment_grades = new stdClass();
            foreach($this->assignment->pluck('id') as $i => $aid){
    
                $assignment = AssignmentSubmission::where('assignment_id', $aid)
                ->where('student_id', $student->id)
                ->select(
                    'grade'
                )
                ->first();
    
                $student->assignment_grades->{"assignment_$i"} = new stdClass();
                $student->assignment_grades->{"assignment_$i"}->title = $this->assignment->first(fn($e) => $e->id == $aid)->name ?? null;
                $student->assignment_grades->{"assignment_$i"}->grade = $assignment->grade ?? '-';
    
            }

        }

        return $this->gradesStudent;
    }

    public function headings(): array
    {

        $data = ['Mahasiswa', 'NIM'];

        foreach($this->assignment ?? [] as $assignment){
            $data[] = $assignment->name;
        }

        return $data;
    }

    public function map($gradeData): array
    {

        $data = [
            $gradeData->fullname,
            $gradeData->nim,
        ];

        foreach($gradeData->assignment_grades as $assignment){
            $data[] = $assignment->grade;
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

    public function title(): string
    {
        return 'Tugas';
    }
}
