<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class GradeHelper
{
    protected $connection = 'moodle_mysql';

    /**
     * Mengambil semua item penilaian dalam course
     */
    public function getCourseGradeItems(int $courseId): Collection
    {
        return DB::connection($this->connection)
            ->table('mdl_grade_items')
            ->where('courseid', $courseId)
            ->get();
    }

    /**
     * Mengambil kategori penilaian dalam course
     */
    public function getCourseGradeCategories(int $courseId): Collection
    {
        return DB::connection($this->connection)
            ->table('mdl_grade_categories')
            ->where('courseid', $courseId)
            ->get();
    }

    /**
     * Mengambil nilai spesifik siswa
     */
    public function getStudentGrade(int $itemId, int $userId): ?object
    {
        return DB::connection($this->connection)
            ->table('mdl_grade_grades')
            ->where([
                'itemid' => $itemId,
                'userid' => $userId
            ])
            ->select([
                'id', 'itemid', 'userid', 'rawgrade', 'rawgrademax',
                'rawgrademin', 'finalgrade', 'hidden', 'locked',
                'overridden', 'excluded', 'feedback'
            ])
            ->first();
    }

    /**
     * Mengambil semua item nilai yang dapat dihitung
     */
    public function getCalculableGradeItems(int $courseId, int $categoryId): Collection
    {
        return DB::connection($this->connection)
            ->table('mdl_grade_items as gi')
            ->where(function($query) use ($courseId, $categoryId) {
                $query->where(function($q) use ($courseId, $categoryId) {
                    $q->whereIn('gi.gradetype', [1, 2]) // numeric atau scale
                      ->where('gi.categoryid', $categoryId)
                      ->where('gi.courseid', $courseId);
                })
                ->orWhere(function($q) use ($courseId, $categoryId) {
                    $q->join('mdl_grade_categories as gc', 'gi.iteminstance', '=', 'gc.id')
                      ->whereIn('gi.itemtype', ['category', 'course'])
                    //   ->where('gc.parent', $categoryId)
                      ->where('gi.courseid', $courseId)
                      ->whereIn('gi.gradetype', [1, 2]);
                });
            })
            ->select('gi.id')
            ->get();
    }

    /**
     * Update nilai siswa
     */
    public function updateStudentGrade(
        int $itemId, 
        int $userId, 
        ?float $rawGrade, 
        float $finalGrade, 
        int $loggedUserId
    ): bool {
        try {

            // Update nilai
            $grade = DB::connection($this->connection)
                ->table('mdl_grade_grades')
                ->where([
                    'itemid' => $itemId,
                    'userid' => $userId
                ])
                ->first();

            if ($grade) {
                // Update nilai yang ada
                DB::connection($this->connection)
                    ->table('mdl_grade_grades')
                    ->where('id', $grade->id)
                    ->update([
                        'rawgrade' => $rawGrade,
                        'finalgrade' => $finalGrade,
                        'timemodified' => time(),
                        'aggregationstatus' => 'used'
                    ]);

                // Catat history
                DB::connection($this->connection)
                    ->table('mdl_grade_grades_history')
                    ->insert([
                        'itemid' => $itemId,
                        'userid' => $userId,
                        'rawgrade' => $rawGrade,
                        'finalgrade' => $finalGrade,
                        'timemodified' => time(),
                        'action' => 2, // update
                        'oldid' => $grade->id,
                        'source' => 'aggregation',
                        'loggeduser' => $loggedUserId
                    ]);
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update bobot agregasi
     */
    public function updateAggregationWeights(int $courseId, int $userId, array $itemIds): void
    {
        $totalItems = count($itemIds);
        if ($totalItems === 0) return;

        $weight = 1 / $totalItems;

        foreach ($itemIds as $itemId) {
            DB::connection($this->connection)
                ->table('mdl_grade_grades')
                ->where([
                    'itemid' => $itemId,
                    'userid' => $userId
                ])
                ->update([
                    'aggregationstatus' => 'used',
                    'aggregationweight' => $weight
                ]);
        }
    }
}