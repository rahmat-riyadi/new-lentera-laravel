<?php

namespace App\Helpers;

use App\Models\Course;

class GlobalHelper {

    static public function rebuildCourseCache($course_id){
        $course = Course::find($course_id);
        $course->update([
            'cacherev' => time(),
            'timemodified' => time()
        ]);
    }

}