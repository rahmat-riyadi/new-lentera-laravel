<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Teacher\AssignmentController;
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

Route::get('/login', function(){
    return view('pages.login');
})->name('login');

Route::group(['middleware' => 'auth'], function(){

    Route::get('/logout', function(){
        Auth::logout();
        return redirect('/login');
    });
    
    Route::get('/', function () {
        return view('pages.index');
    })->name('home');
    
    Route::get('/calendar', function () {
        return view('pages.calendar');
    })->name('calendar');
    
    Route::group(['prefix' => 'course', 'as' => 'course'], function(){
        Route::get('/{course:shortname}', [CourseController::class, 'index']);
    
        Route::group(['prefix' => '{course:shortname}/activity'], function(){
            Route::get('create/{activity}/section/{section}', [ActivityController::class, 'create']);
            Route::get('update/{activity}/instance/{id}/section/{section}', [ActivityController::class, 'edit']);
            Route::get('/{activity}/detail/{courseModule}', [ActivityController::class, 'show']);
        });
        
    });
    
    Route::group(['prefix' => 'teacher'], function(){
        Route::group(['prefix' => 'attendance'], function(){
            Route::get('form/{attendance}', [AttendanceController::class, 'form']);
        });
    
        Route::group(['prefix' => 'assignment'], function(){
            Route::get('{assignment}/grade/{assignmentSubmission}', [AssignmentController::class, 'grade']);
        });
    
        Route::group(['prefix' => 'quiz'], function(){
            Route::get('{quiz}/questions/create', [QuizController::class, 'createQuestion']);
            Route::get('{quiz}/assessment/{studentQuiz}', [QuizController::class, 'assessment']);
        });
    });
    
    Route::group(['prefix' => 'student'], function(){
        Route::group(['prefix' => 'assignment'], function(){
            Route::get('{assignment}/submit', [AssignmentController::class, 'createSubmission']);
        });
    
        Route::group(['prefix' => 'quiz'], function(){
            Route::get('{quiz}/answer', [StudentQuizController::class, 'answer']);
        });
        
    });


    Route::group(['prefix' => 'preview'], function(){
        Route::get('/file/{id}/{fileName}', [FileController::class, 'view']);
    });

});
