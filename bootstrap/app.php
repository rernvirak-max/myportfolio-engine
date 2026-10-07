<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never leak exception details from the API (validation 422 / 429 stay standard).
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            if (! $request->is('api/*') || config('app.debug')) {
                return null;
            }
            if ($e instanceof Illuminate\Validation\ValidationException
                || $e instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }

            return response()->json([
                'message' => 'Sorry, something went wrong on our side. Please try again later.',
            ], 500);
        });
    })->create();
