<?php

namespace App\Http\Controllers\API\Activity;

use App\Helpers\CourseHelper;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\Course;
use App\Models\Module;
use App\Models\Resource;
use App\Models\ResourceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{

    public function findById(Request $request, Resource $resource){

        $file_ctx = DB::connection('moodle_mysql')->table('mdl_context')
        ->where('instanceid', $request->query('cm'))
        ->where('contextlevel', 70)
        ->first('id');

        $files = DB::connection('moodle_mysql')->table('mdl_files')
        ->where('contextid', $file_ctx->id)
        ->where('component', 'mod_resource')
        ->whereNotNull('mimetype')
        ->whereNotNull('source')
        ->orderBy('id')
        ->get();

        $resource->files = $files->map(function($e){

            $ext = explode('.',$e->filename);
            $ext = $ext[count($ext)-1];

            $e->name = $e->filename;
            $e->file = url("/preview/file/$e->id/$e->filename");
            $e->size = $e->filesize;
            $e->itemid = $e->itemid;
            return $e;
        });

        return response()->json([
            'message' => 'Success',
            'data' => $resource
        ], 200);
    }
    public function store(Request $request, $shortname){
        $module = Module::where('name', 'resource')->first();
        $course = Course::where('shortname', $shortname)->first();

        DB::beginTransaction();
        
        try {

            $instance = Resource::create([
                'course' => $course->id,
                'name' => $request->name,
                'intro' => $request->description,
            ]);
            
            $cm = CourseHelper::addCourseModule($course->id, $module->id, $instance->id);

            $context = Context::where('instanceid', $course->id)
            ->where('contextlevel', 50)
            ->first();

            $newContext = Context::create([
                'contextlevel' => 70,
                'instanceid' => $cm->id
            ]);

            $newContext->update([
                'path' => $context->path . '/' . $newContext->id,
                'depth' => substr_count($context->path, '/') + 1
            ]);
            // CourseHelper::addContext($cm->id, $this->course->id);
            CourseHelper::addCourseModuleToSection($course->id, $cm->id, $request->section);

            $this->insertFile($request->itemids, $newContext->id, $instance->id);
            DB::commit();
            GlobalHelper::rebuildCourseCache($course->id);

            return response()->json([
                'message' => 'Success',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

    }

    public function update(Request $request, Resource $resource){

        try {

            $resource->update([
                'name' => $request->name,
                'intro' => $request->description,
            ]);

            $file_ctx = DB::connection('moodle_mysql')->table('mdl_context')
            ->where('instanceid', $request->query('cm'))
            ->where('contextlevel', 70)
            ->first('id');

            $this->insertFile($request->itemids, $file_ctx->id, $resource->id);
    
            GlobalHelper::rebuildCourseCache($resource->course);

            return response()->json([
                'message' => 'Success',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'data' => null
            ], 500);
        }

    }

    public function deleteFile($id){
        DB::connection('moodle_mysql')->table('mdl_files')->where('id', $id)->delete();
        return response()->json([
            'message' => 'Success',
            'data' => null
        ]);
    }

    public function insertFile($itemIds ,$ctxid, $itemid){

        foreach($itemIds as $itId){

            $files = DB::connection('moodle_mysql')->table('mdl_files')
            ->where('itemid', $itId)
            ->orderBy('id')
            ->get()
            ->toArray();

            ResourceFile::create([
                'contenthash' => $files[0]->contenthash,
                'pathnamehash' => GlobalHelper::get_pathname_hash($files[0]->contextid, 'mod_resource', 'content', $files[0]->itemid, '/', $files[0]->filename),
                'contextid' => $ctxid,
                'component' => 'mod_resource',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $files[0]->filename,
                'userid' => $files[0]->userid,
                'filesize' => $files[0]->filesize,
                'mimetype' => $files[0]->mimetype,
                'status' => 0,
                'source' => $files[0]->filename,
                'author' => $files[0]->author,
                'license' => $files[0]->license,
                'timecreated' => $files[0]->timecreated,
                'timemodified' => $files[0]->timemodified,
                'sortorder' => 1,
                'referencefileid' => null,
            ]);
            
            ResourceFile::create([
                'component' => 'mod_resource',
                'filearea' => 'content',
                'contextid' => $ctxid,
                'contenthash' => $files[1]->contenthash,
                'pathnamehash' => GlobalHelper::get_pathname_hash($ctxid, 'mod_resource', 'content', $files[1]->itemid, '/', $files[1]->filename),
                'itemid' => 0,
                'filepath' => '/',
                'userid' => $files[1]->userid,
                'filename' => '.',
                'filesize' => 0,
                'timecreated' => $files[1]->timecreated,
                'timemodified' => $files[1]->timemodified,
                'sortorder' => 0,
            ]);
            

        }

    }
}
