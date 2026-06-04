<?php

// Suppress Laravel 11 vendor deprecations on PHP 8.5 until the framework line is upgraded.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'onboarded' => EnsureOnboardingComplete::class,
        ]);
    })
    ->withProviders([
        AppServiceProvider::class,
        FortifyServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
