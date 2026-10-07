<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('course-enquiries', function (Request $request) {
            return Limit::perMinute((int) config('services.enquiry.rate_limit', 5))
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many enquiries from your connection. Please wait a minute and try again.',
                ], 429));
        });
        //
    }
}
