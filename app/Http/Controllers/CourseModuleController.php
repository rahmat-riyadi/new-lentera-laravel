<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseModuleController extends Controller
{
    public static function addCourseModule($data){
        $cm = CourseModule::create($data);
        return $cm;
    }

    public static function addCourseModuleToSection($course_id, $cmid, $section_num){
        // $course_section = CourseModule::create($data);

        $course_section = CourseSection::where('course', $course_id)
                        ->where('section', $section_num)
                        ->first();

        $sequence = explode(',', trim($course_section->seuqence));

        if(empty($sequence)){
            $newsequence = "$cmid";
        } else {
            $newsequence = "$course_section->sequence,$cmid";
        }

        $course_section->update(['sequence' => $newsequence]);
        CourseModule::find($cmid)->update(['section' => $course_section->id]);
        Helper::rebuild_course_cache($course_id);
        return $course_section;
    }

    public static function delete($id){
        $courseModule = CourseModule::find($id);
        $courseSection = CourseSection::find($courseModule->section);
        $modarray = explode(',', $courseSection->sequence) ;
        $key = array_keys($modarray, $courseModule->id);
        array_splice($modarray, $key[0], 1);
        $newsequence = implode(",", $modarray);
        $courseSection->update(['sequence' => $newsequence]);
        $courseModule->delete();
        Helper::rebuild_course_cache($courseModule->course);
    }

}
