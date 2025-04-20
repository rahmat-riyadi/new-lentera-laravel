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

    public static function get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        if (substr($filepath, 0, 1) != '/') {
            $filepath = '/' . $filepath;
        }
        if (substr($filepath, - 1) != '/') {
            $filepath .= '/';
        }
        return sha1("/$contextid/$component/$filearea/$itemid".$filepath.$filename);
    }

    public static function path_fixed($separator,$path){
        return array_filter(explode($separator, $path), fn($value) => $value !== '');
    }

}