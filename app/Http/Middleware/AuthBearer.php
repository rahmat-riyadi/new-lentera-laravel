<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthBearer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $token = $request->bearerToken();

        Log::info($request->bearerToken());

        [$id, $token] = explode('|', $token, 2);

        $accessToken = DB::connection('moodle_mysql')->table('personal_access_tokens')
        ->where('id', $id)
        ->first();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        if (!hash_equals($accessToken->token, hash('sha256', $token))) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        try {

            
            $user = User::find($accessToken->tokenable_id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Mengotentikasi pengguna ke dalam aplikasi
            Auth::login($user);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
