<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
            "font-src 'self' https://fonts.gstatic.com; ".
            "img-src 'self' data: blob: https:; ".
            "connect-src 'self' https://api.paystack.co https://*.sentry.io ".
            'https://o4511655906770944.ingest.us.sentry.io; '.
            'frame-src https://js.paystack.co https://checkout.paystack.com; '.
            "worker-src 'self' blob:; ".
            "manifest-src 'self'"
        );

        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
