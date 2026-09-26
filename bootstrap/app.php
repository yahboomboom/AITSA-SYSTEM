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
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        ]);
        $middleware->statefulApi();

        // Stops the browser's back/forward cache from redisplaying an
        // authenticated page after logout (or after the session expires) —
        // every web response is marked non-cacheable so Back always
        // re-checks with the server.
        $middleware->web(append: [
            \App\Http\Middleware\PreventBackHistoryCache::class,
        ]);

        // Railway's edge proxy doesn't publish stable IPs to allowlist, so we trust
        // forwarded headers only when actually running on Railway (which always sets
        // this env var) rather than trusting them from any source unconditionally.
        if (env('RAILWAY_ENVIRONMENT')) {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
