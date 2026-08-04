<?php

namespace Tests\Feature;

use App\Livewire\Membership\MembershipSignupWizard;
use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Concerns\FakesHibp;
use Tests\TestCase;

class RegistrationRedirectTest extends TestCase
{
    use FakesHibp;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeHibpWithNoBreach();

        $this->seed([
            RoleSeeder::class,
            InterestSeeder::class,
            GoalSeeder::class,
        ]);
    }

    public function test_pending_onboarding_email_shows_resend_invitation_message(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'status' => 'pending_onboarding',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => $user->name,
            'onboarding_status' => 'pending_onboarding',
        ]);

        Livewire::test(MembershipSignupWizard::class)
            ->set('email', 'pending@example.com')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('password', 'Tmc2024!Sec#Pass99')
            ->set('passwordConfirmation', 'Tmc2024!Sec#Pass99')
            ->call('nextStep')
            ->assertSet('existingMemberDetected', true)
            ->assertSet('showResendButton', true)
            ->assertSee("You're already a member")
            ->assertSee('Resend Invitation');

        // Should NOT have advanced to step 2
        $component = Livewire::test(MembershipSignupWizard::class)
            ->set('email', 'pending@example.com')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('password', 'Tmc2024!Sec#Pass99')
            ->set('passwordConfirmation', 'Tmc2024!Sec#Pass99')
            ->call('nextStep');

        $this->assertEquals(1, $component->get('step'));
    }

    public function test_active_user_email_shows_login_message(): void
    {
        User::factory()->create([
            'email' => 'active@example.com',
            'status' => 'active',
        ]);

        Livewire::test(MembershipSignupWizard::class)
            ->set('email', 'active@example.com')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('password', 'Tmc2024!Sec#Pass99')
            ->set('passwordConfirmation', 'Tmc2024!Sec#Pass99')
            ->call('nextStep')
            ->assertSet('existingMemberDetected', true)
            ->assertSet('showResendButton', false)
            ->assertSee('already exists')
            ->assertSee('Go to Login')
            ->assertSee('Forgot Password');
    }

    public function test_resend_invitation_is_rate_limited_to_one_per_hour(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'rate@example.com',
            'status' => 'pending_onboarding',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => $user->name,
            'onboarding_status' => 'pending_onboarding',
        ]);

        // Trigger detection first
        Livewire::test(MembershipSignupWizard::class)
            ->set('email', 'rate@example.com')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('password', 'Tmc2024!Sec#Pass99')
            ->set('passwordConfirmation', 'Tmc2024!Sec#Pass99')
            ->call('nextStep');

        // First resend succeeds
        Livewire::test(MembershipSignupWizard::class)
            ->set('existingMemberEmail', 'rate@example.com')
            ->call('resendInvitation');

        Notification::assertSentTo($user, OnboardingInvitationNotification::class);

        // Second resend within the hour is rate-limited
        Notification::fake();
        Livewire::test(MembershipSignupWizard::class)
            ->set('existingMemberEmail', 'rate@example.com')
            ->call('resendInvitation')
            ->assertHasErrors('existingMember');
    }

    public function test_resend_invitation_sends_notification_and_sets_invited_at(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'resend@example.com',
            'status' => 'pending_onboarding',
            'invited_at' => null,
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => $user->name,
            'onboarding_status' => 'pending_onboarding',
        ]);

        // Clear any rate limiter from previous tests
        RateLimiter::clear('resend-invite:resend@example.com');

        Livewire::test(MembershipSignupWizard::class)
            ->set('existingMemberEmail', 'resend@example.com')
            ->call('resendInvitation');

        Notification::assertSentTo($user, OnboardingInvitationNotification::class);

        $user->refresh();
        $this->assertNotNull($user->invited_at);
    }

    public function test_new_email_proceeds_normally_through_wizard(): void
    {
        Livewire::test(MembershipSignupWizard::class)
            ->set('email', 'brandnew@example.com')
            ->set('firstName', 'Brand')
            ->set('lastName', 'New')
            ->set('password', 'Tmc2024!Sec#Pass99')
            ->set('passwordConfirmation', 'Tmc2024!Sec#Pass99')
            ->call('nextStep')
            ->assertSet('existingMemberDetected', false)
            ->assertSet('step', 2);
    }
}
