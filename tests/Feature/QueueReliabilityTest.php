<?php

namespace Tests\Feature;

use App\Listeners\LogBillingEvent;
use App\Listeners\LogMembershipEvent;
use App\Listeners\SendBillingNotifications;
use App\Listeners\SendMembershipNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_membership_notifications_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new SendMembershipNotifications);
    }

    public function test_log_membership_event_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new LogMembershipEvent);
    }

    public function test_log_billing_event_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new LogBillingEvent);
    }

    public function test_send_billing_notifications_implements_should_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new SendBillingNotifications);
    }

    public function test_database_queue_has_after_commit_enabled(): void
    {
        $config = config('queue.connections.database');

        $this->assertTrue($config['after_commit'], 'after_commit must be true to prevent jobs firing before DB commits');
    }
}
