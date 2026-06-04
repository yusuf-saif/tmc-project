<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
