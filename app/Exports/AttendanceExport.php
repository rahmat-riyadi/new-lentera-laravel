<?php

namespace App\Exports;

use App\Models\StudentAttendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use stdClass;

class AttendanceExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithTitle, WithStyles
{

    protected $gradesStudent;
    protected $attendance;

    public function __construct($gradesStudent, $attendance)
    {
        $this->gradesStudent = $gradesStudent;
        $this->attendance = $attendance;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        foreach($this->gradesStudent as $student){
            $student->attendance_grades = new stdClass();
            foreach($this->attendance->pluck('id') as $i => $attId){
                $attendance = StudentAttendance::where('attendance_id', $attId)
                ->where('student_id', $student->id)
                ->select(
                    'id',
                    'status'
                )
                ->first();
                $student->attendance_grades->{"attendance_$i"} = new stdClass();
                $student->attendance_grades->{"attendance_$i"}->title = $this->attendance->first(fn($e) => $e->id == $attId)->name ?? null;
                $student->attendance_grades->{"attendance_$i"}->grade = $attendance->status ?? null;
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

        foreach($gradeData->attendance_grades ?? [] as $attendance){
            $data[] = $attendance->grade;
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

        foreach($this->attendance ?? [] as $attendance){
            $data[] = $attendance->name;
        }

        return $data;
    }

    public function title(): string
    {
        return 'Kehadiran';
    }
}
