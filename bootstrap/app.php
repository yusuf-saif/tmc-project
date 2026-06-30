<?php

// Suppress Laravel 11 vendor deprecations on PHP 8.5 until the framework line is upgraded.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

use App\Http\Middleware\EnsureNotSuspendedFromRestrictedAreas;
use App\Http\Middleware\EnsureUserStateRedirect;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'ensure.user.state' => EnsureUserStateRedirect::class,
            'not-suspended' => EnsureNotSuspendedFromRestrictedAreas::class,
        ]);
    })
    ->withProviders([
        AppServiceProvider::class,
        FortifyServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
