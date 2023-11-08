<?php

namespace App\Helpers;

use App\Models\Course;
use Illuminate\Support\Facades\Http;

class Helper {

    public static function purge_caches(){
        $response = Http::asForm()->post(env('MOODLE_URL').'/webservice/rest/server.php',[
            'wstoken' => '765f026a057e5387f672dd3a14c9ed8d',
            'wsfunction' => 'local_cache_management_clear_cache_api',
            'moodlewsrestformat' => 'json',
        ]);
    }

    public static function rebuild_course_cache($course_id){
        Course::find($course_id)->update(['cacherev' => time(), 'timemodified' => time()]);
    }

}

?>