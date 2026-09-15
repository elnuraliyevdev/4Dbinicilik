<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
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
        // This is a session-backed SPA, not a mix of server-rendered pages —
        // there's no Blade login page to redirect a guest to, so every
        // unauthenticated hit on a protected route gets a plain 401 JSON
        // response instead of Laravel's default redirect-to-route('login').
        Authenticate::redirectUsing(fn () => null);
    }
}
