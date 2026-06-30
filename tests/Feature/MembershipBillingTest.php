<?php

namespace Tests\Feature;

use App\Events\MembershipActivated;
use App\Livewire\Home\HomeDashboard;
use App\Livewire\Profile\ProfileScreen;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\MembershipRenewalReminder;
use App\Services\MembershipStateService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipBillingTest extends TestCase
{
    use RefreshDatabase;

    protected MembershipStateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);

        $this->service = app(MembershipStateService::class);
    }

    protected function createActiveUser(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'active'],
        );

        return $user->fresh();
    }

    protected function createMemberUser(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'member',
                'payment_status' => 'paid',
                'current_period_ends_at' => now()->addDays(30),
                'first_paid_at' => now(),
            ],
        );

        return $user->fresh();
    }

    protected function createSuspendedUser(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'suspended',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'suspended',
                'grace_period_ends_at' => now()->subDay(),
            ],
        );

        return $user->fresh();
    }

    // ─── recordPayment tests ───────────────────────────────────────

    public function test_record_payment_sets_member_status(): void
    {
        $user = $this->createActiveUser();
        $profile = $user->memberProfile;

        $this->service->recordPayment($profile, $user);

        $profile->refresh();
        $this->assertEquals('member', $profile->onboarding_status);
        $this->assertEquals('paid', $profile->payment_status);
    }

    public function test_record_payment_sets_billing_dates(): void
    {
        $user = $this->createActiveUser();
        $profile = $user->memberProfile;

        $this->service->recordPayment($profile, $user);

        $profile->refresh();
        $this->assertNotNull($profile->current_period_ends_at);
        $this->assertNotNull($profile->payment_verified_at);
        $this->assertNotNull($profile->first_paid_at);
        $this->assertTrue($profile->current_period_ends_at->diffInDays(now(), absolute: true) <= 30);
    }

    public function test_record_payment_is_idempotent(): void
    {
        $user = $this->createActiveUser();
        $profile = $user->memberProfile;

        $this->service->recordPayment($profile, $user);
        $firstPaidAt = $profile->fresh()->first_paid_at;

        $this->service->recordPayment($profile, $user);
        $profile->refresh();

        $this->assertEquals($firstPaidAt->timestamp, $profile->first_paid_at->timestamp);
        $this->assertEquals('member', $profile->onboarding_status);
    }

    public function test_record_payment_dispatches_event(): void
    {
        Event::fake();

        $user = $this->createActiveUser();
        $profile = $user->memberProfile;

        $this->service->recordPayment($profile, $user);

        Event::assertDispatched(MembershipActivated::class);
    }

    // ─── Grace period tests ────────────────────────────────────────

    public function test_grace_period_does_not_affect_free_users(): void
    {
        $user = $this->createActiveUser();
        $profile = $user->memberProfile;

        $this->service->checkGracePeriod($profile);

        $profile->refresh();
        $this->assertEquals('active', $profile->onboarding_status);
    }

    public function test_grace_period_suspends_expired_member(): void
    {
        $user = $this->createMemberUser();
        $profile = $user->memberProfile;
        $profile->current_period_ends_at = now()->subDays(10);
        $profile->grace_period_ends_at = now()->subDay();
        $profile->save();

        $this->service->checkGracePeriod($profile);

        $profile->refresh();
        $this->assertEquals('suspended', $profile->onboarding_status);
        $this->assertEquals('suspended', $user->fresh()->status);
    }

    public function test_grace_period_sets_expiry_on_first_pass(): void
    {
        $user = $this->createMemberUser();
        $profile = $user->memberProfile;
        $profile->current_period_ends_at = now()->subDay();
        $profile->grace_period_ends_at = null;
        $profile->save();

        $this->service->checkGracePeriod($profile);

        $profile->refresh();
        $this->assertNotNull($profile->grace_period_ends_at);
        $this->assertTrue($profile->grace_period_ends_at->diffInDays(now(), absolute: true) <= 7);
        $this->assertEquals('member', $profile->onboarding_status);
    }

    // ─── Renewal reminder test ─────────────────────────────────────

    public function test_renewal_reminder_sends_notification(): void
    {
        Notification::fake();

        $user = $this->createMemberUser();
        $profile = $user->memberProfile;
        $profile->current_period_ends_at = now()->addDays(3);
        $profile->reminder_sent_at = null;
        $profile->save();

        $this->artisan('membership:send-renewal-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            MembershipRenewalReminder::class,
        );

        $profile->refresh();
        $this->assertNotNull($profile->reminder_sent_at);
    }

    // ─── Home banner test ──────────────────────────────────────────

    public function test_home_banner_shown_for_free_users_hidden_for_members(): void
    {
        $freeUser = $this->createActiveUser();

        $html = Livewire::actingAs($freeUser)
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringContainsString('free plan', $html);
        $this->assertStringContainsString(route('membership.payment'), $html);

        $memberUser = $this->createMemberUser();

        $html = Livewire::actingAs($memberUser)
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringNotContainsString('free plan', $html);
    }

    // ─── Profile section tests ─────────────────────────────────────

    public function test_profile_shows_free_access_section_for_active_status(): void
    {
        $user = $this->createActiveUser();

        $component = Livewire::actingAs($user)->test(ProfileScreen::class);
        $component->set('tab', 'membership');

        $html = $component->html();
        $this->assertStringContainsString('Free Access', $html);
        $this->assertStringContainsString(route('membership.payment'), $html);
    }

    public function test_profile_shows_member_section_with_dates_for_member_status(): void
    {
        $user = $this->createMemberUser();

        $component = Livewire::actingAs($user)->test(ProfileScreen::class);
        $component->set('tab', 'membership');

        $html = $component->html();
        $this->assertStringContainsString('Active Member', $html);
        $this->assertStringContainsString('Valid until', $html);
    }

    public function test_profile_shows_lapsed_section_for_suspended_status(): void
    {
        $user = $this->createSuspendedUser();

        $component = Livewire::actingAs($user)->test(ProfileScreen::class);
        $component->set('tab', 'membership');

        $html = $component->html();
        $this->assertStringContainsString('Membership Lapsed', $html);
        $this->assertStringContainsString(route('membership.payment'), $html);
    }
}
