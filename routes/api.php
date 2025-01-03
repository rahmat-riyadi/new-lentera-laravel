<?php

use App\Http\Controllers\API\Activity\ActivityController;
use App\Http\Controllers\API\Activity\AssignmentController;
use App\Http\Controllers\API\Activity\AttendanceController;
use App\Http\Controllers\API\Activity\FileController as ActivityFileController;
use App\Http\Controllers\API\Activity\QuizController;
use App\Http\Controllers\API\Activity\UrlController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth.bearer')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.bearer')->group(function () {

    Route::group(['prefix' => 'course'], function(){
        Route::get('/', [CourseController::class, 'getAllCourse']);    
        Route::get('/can-import', [CourseController::class, 'getAllCanImportCourse']);
        
        Route::group(['prefix' => '{shortname}'], function(){
            Route::get('/', [CourseController::class, 'getTopic']);    
            Route::get('/participants', [CourseController::class, 'getParticipants']);

            Route::get('/section', [CourseController::class, 'addTopic']);

            Route::post('/import', [CourseController::class, 'importCourse']);
            
            Route::group(['prefix' => 'activity'], function(){
                Route::post('/url', [UrlController::class, 'store']);    
                Route::post('/resource', [ActivityFileController::class, 'store']);    
                Route::post('/quiz', [QuizController::class, 'store']);    
                Route::post('/attendance', [AttendanceController::class, 'store']);    

                
                Route::group(['prefix' => 'assignment'], function(){
                    Route::get('/detail/{assignment}', [AssignmentController::class, 'detail']);    
                    Route::post('/', [AssignmentController::class, 'store']);    
                });
                
                Route::delete('/{id}', [ActivityController::class, 'destroy']);
            });
            
            Route::put('/{section}', [CourseController::class, 'changeTopic']);    
        });
        
    });
    
    Route::group(['prefix' => 'url'], function(){
        Route::get('/{url}', [UrlController::class, 'findById']);
        Route::put('/{url}', [UrlController::class, 'update']);
    });

    Route::group(['prefix' => 'attendance'], function(){

        Route::post('/submit-student', [AttendanceController::class, 'setStudentAttendance']);

        Route::put('session/{sessionId}', [AttendanceController::class, 'updateSession']);

        Route::get('/{attendance}', [AttendanceController::class, 'findById']);
        Route::get('/{attendance}/detail', [AttendanceController::class, 'getSession']);
        Route::get('/{attendance}/session/{sessionId}', [AttendanceController::class, 'getSessionDetail']);
        Route::post('/{attendance}/session/{sessionId}', [AttendanceController::class, 'saveSessionDetail']);
        Route::post('/{attendance}/session', [AttendanceController::class, 'addSession']);
        Route::put('/{attendance}/session/delete', [AttendanceController::class, 'deleteSession']);
        Route::put('/{attendance}', [AttendanceController::class, 'update']);
    });

    Route::group(['prefix' => 'assignment'], function(){
        Route::get('/{assignment}', [AssignmentController::class, 'findById']);
        Route::get('/grade/{assignmentSubmission}', [AssignmentController::class, 'getDetailGrading']);
        Route::put('/{assignment}', [AssignmentController::class, 'update']);

        Route::group(['prefix' => 'student'], function(){
            Route::get('/{assignment}', [AssignmentController::class, 'detailForStudent']);
            Route::post('/{assignment}', [AssignmentController::class, 'submitSubmission']);
            Route::delete('/submission/file/{id}/', [AssignmentController::class, 'deleteFileSubmission']);
        });

    });

    Route::group(['prefix' => 'quiz'], function(){
        Route::get('/{quiz}', [QuizController::class, 'findById']);

        Route::group(['prefix' => '{quiz}/student'], function(){
            Route::get('/start-attempt', [QuizController::class, 'studentAttemptingQuiz']);
            Route::get('/attempt', [QuizController::class, 'getStudentAnswerStateData']);
            Route::post('/save-attempt', [QuizController::class, 'saveStudentState']);
            // Route::post('/{quiz}', [QuizController::class, 'submitQuiz']);
        });

        Route::group(['prefix' => '{shortname}/questions'], function(){
            Route::get('/', [QuizController::class, 'getBankQuestion']);
            Route::get('/{question}', [QuizController::class, 'getQuestionById']);
            Route::get('/quiz/{quiz}', [QuizController::class, 'getQuizQuestion']);

            Route::post('/', [QuizController::class, 'storeQuestion']);
            Route::post('/{question}', [QuizController::class, 'updateQuestion']);
            Route::post('/insert-question/{quiz}', [QuizController::class, 'insertQuestionToQuiz']);
            Route::post('/store-question-and-insert/{quiz}', [QuizController::class, 'storeQuestionAndInsertToQuiz']);

            Route::delete('/{question}', [QuizController::class, 'deleteQuestion']);
            Route::delete('/quiz/{quiz}/slot/{slotid}', [QuizController::class, 'deleteQuestionFromQuiz']);
        });

        Route::put('/{quiz}', [QuizController::class, 'update']);
    });

    Route::group(['prefix' => 'resource'], function(){
        Route::get('/{resource}', [ActivityFileController::class, 'findById']);
        Route::put('/{resource}', [ActivityFileController::class, 'update']);
        Route::delete('/file/{id}', [ActivityFileController::class, 'deleteFile']);
    });
   
});

Route::get('/preview/file/{id}/{fileName}', [FileController::class, 'view']);

Route::post('/file', [FileController::class, 'upload']);


Route::post('/question/image', [ImageController::class, 'storeQuestionImage']);
