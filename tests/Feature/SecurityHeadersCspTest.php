<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersCspTest extends TestCase
{
    public function test_csp_header_includes_r2_storage_domain(): void
    {
        $middleware = new SecurityHeaders;

        $response = $middleware->handle(
            Request::create('/'),
            fn () => new Response('ok'),
        );

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://*.r2.cloudflarestorage.com', $csp);
    }

    public function test_csp_header_does_not_contain_duplicate_r2_domain(): void
    {
        $middleware = new SecurityHeaders;

        $response = $middleware->handle(
            Request::create('/'),
            fn () => new Response('ok'),
        );

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertSame(1, substr_count((string) $csp, 'r2.cloudflarestorage.com'));
    }

    public function test_r2_domain_is_within_connect_src_directive(): void
    {
        $middleware = new SecurityHeaders;

        $response = $middleware->handle(
            Request::create('/'),
            fn () => new Response('ok'),
        );

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);

        $connectSrc = null;
        foreach (explode(';', $csp) as $directive) {
            $parts = preg_split('/\s+/', trim($directive));
            if (($parts[0] ?? null) === 'connect-src') {
                $connectSrc = $parts;
                break;
            }
        }

        $this->assertNotNull($connectSrc);
        $this->assertContains('https://*.r2.cloudflarestorage.com', $connectSrc);
    }
}
