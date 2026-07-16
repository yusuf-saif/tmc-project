<?php

namespace Tests\Feature;

use App\Console\Commands\QueueHealthSweep;
use App\Models\User;
use App\Notifications\QueueHealthAlertNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QueueHealthSweepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_no_delayed_jobs_reports_clean(): void
    {
        $this->artisan('queue:health-sweep --no-drain')
            ->assertExitCode(0)
            ->expectsOutputToContain('No delayed jobs detected');
    }

    public function test_delayed_jobs_triggers_alert_and_notification(): void
    {
        Notification::fake();

        // Create admin users who will receive the alert
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('super_admin');

        // Insert a synthetic old job row (>15 minutes old)
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'test', 'data' => []]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(20),
        ]);

        $this->artisan('queue:health-sweep --no-drain')
            ->assertExitCode(0)
            ->expectsOutputToContain('delayed job(s) detected')
            ->expectsOutputToContain('Alerted');

        Notification::assertSentTo($admin, QueueHealthAlertNotification::class);
    }

    public function test_no_drain_flag_skips_drain_report(): void
    {
        // Insert an old job
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'test', 'data' => []]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(20),
        ]);

        $this->artisan('queue:health-sweep --no-drain')
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('Draining stuck jobs')
            ->doesntExpectOutputToContain('Drain complete');
    }

    public function test_drain_mode_runs_queue_work(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('super_admin');

        // Insert an old job
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'test', 'data' => []]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(20),
        ]);

        $this->artisan('queue:health-sweep')
            ->assertExitCode(0)
            ->expectsOutputToContain('Draining stuck jobs')
            ->expectsOutputToContain('Drain complete');

        Notification::assertSentTo($admin, QueueHealthAlertNotification::class);
    }

    public function test_command_exits_successfully_with_no_delayed_jobs(): void
    {
        $this->artisan('queue:health-sweep')
            ->assertExitCode(0);
    }
}
