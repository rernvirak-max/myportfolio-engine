<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\CourseEnquiryController as AdminCourseEnquiryController;
use App\Http\Controllers\Api\Admin\OverviewController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseEnquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{slug}', [CourseController::class, 'show']);

Route::post('/course-enquiries', [CourseEnquiryController::class, 'store'])
    ->middleware('throttle:course-enquiries');

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/stats', StatsController::class);
        Route::get('/overview', OverviewController::class);

        Route::get('/course-enquiries/export', [AdminCourseEnquiryController::class, 'export']);
        Route::get('/course-enquiries', [AdminCourseEnquiryController::class, 'index']);
        Route::get('/course-enquiries/{courseEnquiry}', [AdminCourseEnquiryController::class, 'show']);
        Route::patch('/course-enquiries/{courseEnquiry}', [AdminCourseEnquiryController::class, 'update']);
        Route::delete('/course-enquiries/{courseEnquiry}', [AdminCourseEnquiryController::class, 'destroy']);

        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::post('/courses', [AdminCourseController::class, 'store']);
        Route::get('/courses/{course}', [AdminCourseController::class, 'show']);
        Route::patch('/courses/{course}', [AdminCourseController::class, 'update']);
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);

        Route::post('/courses/{course}/modules', [AdminCourseController::class, 'storeModule']);
        Route::patch('/courses/{course}/modules/reorder', [AdminCourseController::class, 'reorderModules']);
        Route::patch('/courses/{course}/modules/{module}', [AdminCourseController::class, 'updateModule']);
        Route::delete('/courses/{course}/modules/{module}', [AdminCourseController::class, 'destroyModule']);

        Route::post('/courses/{course}/cohorts', [AdminCourseController::class, 'storeCohort']);
        Route::patch('/courses/{course}/cohorts/{cohort}', [AdminCourseController::class, 'updateCohort']);
        Route::delete('/courses/{course}/cohorts/{cohort}', [AdminCourseController::class, 'destroyCohort']);
    });
});
