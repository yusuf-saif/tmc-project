<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IP addresses / CIDR ranges of reverse proxies that the
    | application may sit behind. Leave empty to trust no forwarded headers.
    |
    | Railway (production) does not publish stable proxy CIDR ranges, so this
    | stays empty by default and the real client IP is taken from the
    | `X-Real-IP` header that Railway's edge proxy always sets (see
    | App\Http\Middleware\TrustRealIpHeader). Set this only if a known reverse
    | proxy (e.g. a custom CDN/load balancer) is added in front of the app.
    |
    */

    'proxies' => array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', '')),
    )),

];
