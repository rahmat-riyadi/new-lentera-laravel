<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function storeQuestionImage(Request $request){
        $location = $request->file('file')->store('question-image');
        return response()->json([
            'location' => "/storage/$location",
        ]);
    }
}
