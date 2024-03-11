<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function storeQuestionImage(Request $request){

        try {
            $location = $request->file('file')->store('tinymce-image');
            return response()->json([
                'location' => "/storage/$location",
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            //throw $th;
            return response()->json(['location' => $th->getMessage()]);
        }

    }
}
