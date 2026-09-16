<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // The claim-account form is a standalone page opened straight from a
        // signed link (no prior page load to seed a CSRF cookie/token for an
        // anonymous visitor) — the URL signature itself is the forgery
        // protection here, so CSRF verification is redundant on top of it.
        $middleware->validateCsrfTokens(except: ['claim-account/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
