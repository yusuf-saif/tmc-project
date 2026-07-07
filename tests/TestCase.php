<?php

namespace Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear signup rate limiter so previous local runs don't carry over when
        // .env has CACHE_STORE=file and variables_order=GPCS prevents PHPUnit's
        // <env name="CACHE_STORE" value="array"/> from taking effect.
        Cache::forget('signup:127.0.0.1');

        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        set_error_handler(static function (int $severity, string $message): bool {
            return $severity === E_DEPRECATED
                && str_contains($message, 'PDO::MYSQL_ATTR_SSL_CA is deprecated');
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        parent::tearDown();
    }
}
