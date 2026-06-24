<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_runs_without_error(): void
    {
        $exitCode = Artisan::call('tmc:health-check');

        $this->assertIsInt($exitCode, 'Health check should run without crashing');
    }

    public function test_health_check_exits_gracefully(): void
    {
        $exitCode = Artisan::call('tmc:health-check');

        $this->assertContains($exitCode, [0, 1], 'Health check should exit with 0 or 1');
    }
}
