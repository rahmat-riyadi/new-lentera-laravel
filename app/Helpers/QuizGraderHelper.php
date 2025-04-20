<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class QuizGraderHelper
{
    protected $connection = 'moodle_mysql';

    /**
     * Mengambil informasi quiz dan bobotnya
     */
    public function getQuizInfo(int $quizId)
    {
        return DB::connection($this->connection)
            ->table('mdl_quiz as q')
            ->join('mdl_grade_items as gi', function($join) {
                $join->on('gi.iteminstance', '=', 'q.id')
                     ->where('gi.itemmodule', '=', 'quiz');
            })
            ->where('q.id', $quizId)
            ->select([
                'q.id',
                'q.grade as max_grade',
                'q.sumgrades as total_question_marks',
                'gi.grademax as grade_item_max',
                'gi.aggregationcoef as weight_coefficient',
                'gi.aggregationcoef2 as weight_coefficient2'
            ])
            ->first();
    }

    /**
     * Mengambil nilai setiap soal dalam quiz
     */
    public function getQuestionGrades(int $attemptId)
    {
        return DB::connection($this->connection)
            ->table('mdl_question_attempts as qa')
            ->join('mdl_question_attempt_steps as qas', function($join) {
                $join->on('qas.questionattemptid', '=', 'qa.id')
                     ->whereRaw('qas.sequencenumber = (
                         SELECT MAX(sequencenumber) 
                         FROM mdl_question_attempt_steps 
                         WHERE questionattemptid = qa.id
                     )');
            })
            ->where('qa.questionusageid', $attemptId)
            ->select([
                'qa.slot',
                'qa.maxmark',
                'qas.fraction',
                DB::raw('(qa.maxmark * qas.fraction) as mark_obtained')
            ])
            ->get();
    }

    /**
     * Menghitung nilai akhir quiz
     */
    public function calculateQuizGrade(int $quizId, int $attemptId)
    {
        // Ambil info quiz
        $quizInfo = $this->getQuizInfo($quizId);
        
        // Ambil nilai per soal
        $questionGrades = $this->getQuestionGrades($attemptId);
        
        // Hitung total nilai yang diperoleh
        $totalObtained = $questionGrades->sum('mark_obtained');
        
        // Hitung nilai akhir berdasarkan skala quiz
        if ($quizInfo->total_question_marks > 0) {
            $finalGrade = ($totalObtained / $quizInfo->total_question_marks) * $quizInfo->max_grade;
        } else {
            $finalGrade = 0;
        }
        
        return [
            'raw_grade' => $totalObtained,
            'final_grade' => $finalGrade,
            'max_possible' => $quizInfo->max_grade,
            'total_question_marks' => $quizInfo->total_question_marks,
            'weight' => $quizInfo->weight_coefficient2 ?? $quizInfo->weight_coefficient
        ];
    }

    /**
     * Menyimpan nilai quiz
     */
    public function saveQuizGrade(int $quizId, int $userId, float $grade)
    {
        try {

            // Update quiz_grades
            DB::connection($this->connection)
                ->table('mdl_quiz_grades')
                ->updateOrInsert(
                    [
                        'quiz' => $quizId,
                        'userid' => $userId
                    ],
                    [
                        'grade' => $grade,
                        'timemodified' => time()
                    ]
                );

            // Update grade_grades
            $gradeItem = DB::connection($this->connection)
                ->table('mdl_grade_items')
                ->where([
                    'itemmodule' => 'quiz',
                    'iteminstance' => $quizId
                ])
                ->first();

            if ($gradeItem) {
                DB::connection($this->connection)
                ->table('mdl_grade_grades')
                ->updateOrInsert(
                    [
                        'itemid' => $gradeItem->id,
                        'userid' => $userId
                    ],
                    [
                        'rawgrade' => $grade,
                        'finalgrade' => $grade,
                        'timemodified' => time()
                    ]
                );

                $g = DB::connection($this->connection)
                ->table('mdl_grade_grades')
                ->where([
                    'itemid' => $gradeItem->id,
                    'userid' => $userId
                ])->first();

                DB::connection($this->connection)
                ->table('mdl_grade_grades_history')
                ->insert([
                    'itemid' => $gradeItem->id,
                    'userid' => $userId,
                    'rawgrade' => $grade,
                    'finalgrade' => $grade,
                    'timemodified' => time(),
                    'action' => 2, // update
                    'oldid' => $g->id,
                    'source' => 'aggregation',
                    'loggeduser' => $userId
                ]);
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Menghitung bobot soal dalam quiz
     */
    public function calculateQuestionWeights(int $quizId)
    {
        $quiz = DB::connection($this->connection)
            ->table('mdl_quiz')
            ->where('id', $quizId)
            ->first();

        $questions = DB::connection($this->connection)
            ->table('mdl_quiz_slots')
            ->where('quizid', $quizId)
            ->select(['slot', 'maxmark'])
            ->get();

        // Hitung total marks
        $totalMarks = $questions->sum('maxmark');

        // Hitung bobot per soal
        return $questions->map(function($question) use ($totalMarks) {
            return [
                'slot' => $question->slot,
                'weight' => $totalMarks > 0 ? ($question->maxmark / $totalMarks) : 0
            ];
        });
    }
}