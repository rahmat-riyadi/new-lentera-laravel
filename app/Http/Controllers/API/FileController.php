<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    public function view($id){
        $file = DB::connection('moodle_mysql')->table('mdl_files')->where('id', $id)->first();

        $filedir = substr($file->contenthash, 0, 4);
        $formatted_dir = substr_replace($filedir, '/', 2, 0);

        
        $filePath = public_path("moodledir/$formatted_dir/$file->contenthash");
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $fileContent = file_get_contents($filePath);
        
        return response($fileContent,200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Content-Type', $mimeType);
    }

    public function upload(Request $request){

        $request->header('Access-Control-Allow-Origin', '*');

        $f = [];

        foreach($request->file('file') as $file){
            $f[] = $file->getClientOriginalName();
        }        

        return response()
        ->json([
            'token' => $request->header('wstoken'),
            'message' => 'Success',
            'data' => $f
        ],200);

    }

}
