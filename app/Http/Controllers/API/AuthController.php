<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request){

        $response = Http::get(env('MOODLE_URL').'/login/token.php', [
            'username' => $request->username,
            'password' => $request->password,
            'service' => 'new-lentera-service',
        ]);

        if(!$response->ok()){
            return response()->json([
                'message' => 'internal server error',
                'data' => null
            ], 500);
        }

        if(!empty($response->json()['error'])){
            return response()->json([
                'message' => 'Unauthorized',
                'data' => 'Username atau Password salah'
            ], 401);
        }

        $userid = DB::connection('moodle_mysql')->table('mdl_external_tokens')
        ->where('token', $response->json()['token'])
        ->first('userid');

        $user = User::find($userid->userid);

        Auth::login($user);

        $course = Course::
        whereIn('mdl_course.id', function($q) use ($request){
            $q->select('e.courseid')
            ->from('mdl_enrol as e')
            ->join('mdl_user_enrolments as ue', function ($join) use ($request) {
                $join->on('ue.enrolid', '=', 'e.id')
                    ->where('ue.userid', '=', $request->user()->id);
            })
            ->join('mdl_course as c', 'c.id', '=', 'e.courseid')
            ->where('ue.status', '=', '0')
            ->where('e.status', '=', '0');
        })
        ->select(
            'mdl_course.id',
        )
        ->first();

        $ctx = Context::where('contextlevel', 50)->where('instanceid', $course->id)->first();
        $data = DB::connection('moodle_mysql')->table('mdl_role_assignments as ra')
        ->join('mdl_role as r', 'r.id', '=', 'ra.roleid')
        ->where('ra.contextid', $ctx->id)
        ->where('ra.userid', auth()->user()->id)
        ->select(
            'r.shortname as role',
        )
        ->first();
        

        $token = auth()->user()->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'name' => auth()->user()->firstname . ' ' . auth()->user()->lastname,
            'nim' => auth()->user()->username,
            'wstoken' => $response->json()['token'],
            'role' => $data->role,
        ], 200);

    }
}
