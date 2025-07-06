<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register global middleware here
        // $middleware->append(...);

        // Optionally: register aliases to use in routes
        $middleware->alias([
            'auth.jwt' => JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // exception handlers (optional)
    })
    ->booted(function () {
        // Load API routes manually
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));
    })
    ->create();
