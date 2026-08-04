<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustRealIpHeader
{
    /**
     * Trust the `X-Real-IP` header set by Railway's edge proxy.
     *
     * Railway's public networking contract (Specs & Limits) states that
     * `X-Real-IP` is the header its edge proxy sets to identify the client's
     * remote IP, that the header is always overwritten by the proxy, and that
     * the origin cannot be reached without going through the proxy — so a
     * client-supplied value cannot reach the application.
     *
     * The header is only honoured when it parses as a valid IP address, so a
     * malformed value silently falls back to the real socket address.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $realIp = $request->header('X-Real-IP');

        if (is_string($realIp) && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
            $request->server->set('REMOTE_ADDR', $realIp);
        }

        return $next($request);
    }
}
