<?php

use App\Http\Controllers\Api\CourseEnquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/course-enquiries', [CourseEnquiryController::class, 'store'])
    ->middleware('throttle:course-enquiries');
