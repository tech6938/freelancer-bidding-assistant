<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckBanned::class,
        ]);

        $middleware->alias([
            'freelancer.connected' => \App\Http\Middleware\EnsureFreelancerConnected::class,
            'admin'                => \App\Http\Middleware\AdminMiddleware::class,
            'banned'               => \App\Http\Middleware\CheckBanned::class,
            'user'                 => \App\Http\Middleware\UserMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booted(function () {
        // Strict rate limiter for bid placement (10 per minute per user)
        RateLimiter::for('bid', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter for AI proposal generation (5 per minute per user)
        RateLimiter::for('ai-proposal', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter for live feed polling (30 per minute per user)
        RateLimiter::for('live-feed', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    })->create();

