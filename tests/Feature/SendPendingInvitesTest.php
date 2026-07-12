<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPendingInvitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_dry_run_lists_only_eligible_users(): void
    {
        Notification::fake();

        // Eligible: pending_onboarding + invited_at NULL
        $eligible1 = User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => null]);
        $eligible2 = User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => null]);

        // Not eligible: already invited
        User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => now()]);

        // Not eligible: wrong status
        User::factory()->create(['status' => 'active', 'invited_at' => null]);

        $this->artisan('members:send-pending-invites', ['--dry-run' => true])
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_dry_run_does_not_set_invited_at(): void
    {
        $user = User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => null]);

        $this->artisan('members:send-pending-invites', ['--dry-run' => true]);

        $user->refresh();
        $this->assertNull($user->invited_at);
    }

    public function test_sends_invitation_and_sets_invited_at(): void
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => null, 'member_id' => 'TMC-M-1447-001']);

        $this->artisan('members:send-pending-invites')
            ->assertExitCode(0);

        Notification::assertSentTo($user, OnboardingInvitationNotification::class);

        $user->refresh();
        $this->assertNotNull($user->invited_at);
    }

    public function test_respects_limit_option(): void
    {
        Notification::fake();

        // Create 5 eligible users
        for ($i = 0; $i < 5; $i++) {
            User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => null]);
        }

        $this->artisan('members:send-pending-invites', ['--limit' => 3])
            ->assertExitCode(0);

        // Only 3 should have been invited
        $this->assertEquals(3, User::whereNotNull('invited_at')->where('status', 'pending_onboarding')->count());
        $this->assertEquals(2, User::whereNull('invited_at')->where('status', 'pending_onboarding')->count());
    }

    public function test_idempotent_does_not_reinvite(): void
    {
        Notification::fake();

        // User already invited
        $user = User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => now()]);

        $this->artisan('members:send-pending-invites')
            ->assertExitCode(0);

        Notification::assertNotSentTo($user, OnboardingInvitationNotification::class);
    }

    public function test_orders_by_id_and_respects_limit(): void
    {
        Notification::fake();

        $users = collect();
        for ($i = 0; $i < 5; $i++) {
            $users->push(User::factory()->create(['status' => 'pending_onboarding', 'invited_at' => null]));
        }

        $lowestIds = $users->pluck('id')->sort()->take(3)->values();

        $this->artisan('members:send-pending-invites', ['--limit' => 3])
            ->assertExitCode(0);

        $invitedIds = User::whereNotNull('invited_at')->pluck('id')->sort()->values();
        $this->assertEquals($lowestIds, $invitedIds);
    }
}
