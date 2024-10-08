<?php

namespace App\Helpers;

use App\Models\Context;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;

class CourseHelper {

    static public function addCourseModule($course_id, $module, $instance) : CourseModule {
        return CourseModule::create([
            'course' => $course_id,
            'module' => $module,
            'instance' => $instance,
            'showdescription' => 1,
            'added' => time()
        ]);
    }

    static public function addContext($cmid, $course_id){

        $context = Context::where('instanceid', $course_id)
        ->where('contextlevel', 50)
        ->first();

        $newContext = Context::create([
            'contextlevel' => 70,
            'instanceid' => $cmid
        ]);

        $newContext->update([
            'path' => $context->path . '/' . $newContext->id,
            'depth' => substr_count($context->path, '/') + 1
        ]);

        return $newContext;

    }

    static public function addCourseModuleToSection($course_id, $cmid, $section_num){

        $courseSection = CourseSection::where('course', $course_id)
        ->where('section', $section_num)
        ->first();

        $sequence = explode(',', trim($courseSection->sequence));

        if(empty($sequence)){
            $newsequence = "$cmid";
        } else {
            $newsequence = "$courseSection->sequence,$cmid";
        }

        $courseSection->update(['sequence' => $newsequence]);
        CourseModule::find($cmid)->update(['section' => $courseSection->id]);
        GlobalHelper::rebuildCourseCache($course_id);
    }

}