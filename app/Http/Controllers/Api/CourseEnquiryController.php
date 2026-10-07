<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseEnquiryRequest;
use App\Jobs\NotifyCourseEnquiry;
use App\Models\CourseEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CourseEnquiryController extends Controller
{
    private const THANKS = 'Thanks! Your enquiry has been received — I’ll get back to you soon.';

    public function store(StoreCourseEnquiryRequest $request): JsonResponse
    {
        // Honeypot: pretend success, store nothing.
        if (filled($request->input('website'))) {
            Log::info('Course enquiry honeypot triggered.', ['ip' => $request->ip()]);

            return response()->json(['message' => self::THANKS], 201);
        }

        try {
            $enquiry = CourseEnquiry::create([
                ...$request->safe()->except('website'),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Sorry, something went wrong on our side. Please try again later or contact me on Telegram.',
            ], 500);
        }

        try {
            config('services.enquiry.queue')
                ? NotifyCourseEnquiry::dispatch($enquiry)
                : NotifyCourseEnquiry::dispatchSync($enquiry);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['message' => self::THANKS], 201);
    }
}
