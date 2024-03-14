<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\QuizController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/logout', function(){
    Auth::logout();
    return redirect('/login');
})->middleware('auth');

Route::get('/login', function(){
    return view('pages.login');
})->name('login');

Route::get('/', function () {
    return view('pages.index');
})->middleware('auth');

Route::group(['prefix' => 'course'], function(){
    Route::get('/{course:shortname}', [CourseController::class, 'index']);

    Route::group(['prefix' => '{course:shortname}/activity'], function(){
        Route::get('create/{activity}/section/{section}', [ActivityController::class, 'create']);
        Route::get('update/{activity}/instance/{id}/section/{section}', [ActivityController::class, 'edit']);
        Route::get('/{activity}/detail/{courseModule}', [ActivityController::class, 'show']);
    });
    
})->middleware('auth');

Route::group(['prefix' => 'teacher'], function(){
    Route::group(['prefix' => 'attendance'], function(){
        Route::get('form/{attendance}', [AttendanceController::class, 'form']);
    });

    Route::group(['prefix' => 'quiz'], function(){
        Route::get('{quiz}/questions/create', [QuizController::class, 'createQuestion']);
    });
})->middleware('auth');