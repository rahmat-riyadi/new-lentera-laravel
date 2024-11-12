<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request){

        Log::info($request->all());

        if(!Auth::attempt(['username' => $request->username, 'password' => $request->password])){
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $response = Http::get(env('MOODLE_URL').'/login/token.php', [
            'username' => $request->username,
            'password' => $request->password,
            'service' => 'new-lentera-service',
        ]);

        Log::info($response);

        if($response->ok()){
            $wstoken = $response->json()['token'];
        }

        $token = auth()->user()->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'name' => auth()->user()->firstname . ' ' . auth()->user()->lastname,
            'nim' => auth()->user()->username,
            'wstoken' => $wstoken
        ], 200);

    }
}
