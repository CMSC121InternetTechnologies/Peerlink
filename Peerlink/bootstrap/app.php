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
        // Adds CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy, etc.
        // to every response — defense-in-depth against XSS and clickjacking.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Aliases — referenced from routes/web.php as `'no-cache'`.
        // PreventBackHistory sends Cache-Control: no-store on the response so
        // the browser can't show the dashboard from history after logout.
        $middleware->alias([
            'no-cache' => \App\Http\Middleware\PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
