<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustRealIpHeader;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustProxyTest extends TestCase
{
    public function test_spoofed_x_forwarded_for_is_ignored_when_no_trusted_proxies(): void
    {
        $request = Request::create('/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ]);

        $this->assertNotEquals('1.2.3.4', $request->ip());
    }

    public function test_x_real_ip_sets_remote_addr(): void
    {
        $request = Request::create('/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_REAL_IP' => '203.0.113.50',
        ]);

        $middleware = new TrustRealIpHeader;
        $middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals('203.0.113.50', $request->ip());
    }

    public function test_invalid_x_real_ip_is_ignored(): void
    {
        $request = Request::create('/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_REAL_IP' => 'not-an-ip',
        ]);

        $middleware = new TrustRealIpHeader;
        $middleware->handle($request, fn ($req) => response('ok'));

        $this->assertEquals('127.0.0.1', $request->ip());
    }
}
