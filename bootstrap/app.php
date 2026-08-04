<?php

// Suppress Laravel 11 vendor deprecations on PHP 8.5 until the framework line is upgraded.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

use App\Http\Middleware\EnsureNotSuspendedFromRestrictedAreas;
use App\Http\Middleware\EnsureUserStateRedirect;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustRealIpHeader;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Sentry\Laravel\ServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        // Do not trust forwarded headers by default. Railway does not publish
        // stable proxy CIDR ranges, so the real client IP is taken from the
        // `X-Real-IP` header Railway's edge proxy always sets (see
        // TrustRealIpHeader). Operators may opt into `X-Forwarded-*` trust for
        // a known reverse proxy by setting TRUSTED_PROXIES (config/trustedproxy.php).
        $middleware->trustProxies(
            at: array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))),
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append(TrustRealIpHeader::class);

        $middleware->alias([
            'ensure.user.state' => EnsureUserStateRedirect::class,
            'not-suspended' => EnsureNotSuspendedFromRestrictedAreas::class,
        ]);
    })
    ->withProviders([
        AppServiceProvider::class,
        FortifyServiceProvider::class,
        ServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
