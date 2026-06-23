<?php

namespace Tests\Feature;

use App\Livewire\Membership\MembershipSignupWizard;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\JannahCoinsLedger;
use App\Models\MembershipOnboardingDraft;
use App\Models\User;
use App\Models\UserReferral;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            InterestSeeder::class,
            GoalSeeder::class,
        ]);
    }

    protected function createDraft(): MembershipOnboardingDraft
    {
        return MembershipOnboardingDraft::create([
            'payload' => [],
            'step' => 1,
            'status' => 'draft',
        ]);
    }

    public function test_user_can_register_and_is_redirected_to_membership_signup(): void
    {
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->assertSet('step', 1);
    }

    public function test_user_can_complete_signup_and_is_redirected_to_pending(): void
    {
        $draft = $this->createDraft();
        $interestSlugs = Interest::query()->orderBy('sort_order')->limit(2)->pluck('slug')->all();
        $goalSlugs = Goal::query()->orderBy('id')->limit(2)->pluck('slug')->all();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->assertSet('step', 1)
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->set('email', 'aisha@example.com')
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'married')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('phone', '+2348000000000')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('igUsername', 'aisha_m')
            ->set('xUsername', 'aisha_x')
            ->call('nextStep')
            ->assertSet('step', 5)
            ->set('preferredBillingCycle', 'monthly')
            ->call('nextStep')
            ->assertSet('step', 6)
            ->call('toggleInterest', $interestSlugs[0])
            ->call('toggleGoal', $goalSlugs[0])
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $user = User::query()->where('email', 'aisha@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending_review', $user->memberProfile->onboarding_status);
        $this->assertNotNull($user->memberProfile->submitted_at);
        $this->assertEquals('pending_review', $user->status);
    }

    public function test_double_submit_is_prevented(): void
    {
        $draft = $this->createDraft();
        $interestSlugs = Interest::query()->orderBy('sort_order')->limit(2)->pluck('slug')->all();
        $goalSlugs = Goal::query()->orderBy('id')->limit(2)->pluck('slug')->all();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->set('email', 'aisha@example.com')
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'married')
            ->set('phone', '+2348000000000')
            ->set('preferredBillingCycle', 'monthly')
            ->call('toggleInterest', $interestSlugs[0])
            ->call('toggleGoal', $goalSlugs[0])
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $user = User::query()->where('email', 'aisha@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(MembershipSignupWizard::class)
            ->assertRedirect(route('membership.pending'));
    }

    public function test_referred_user_registration_tracks_the_referrer_for_future_awards(): void
    {
        $referrer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'REFER123',
        ]);
        $referrer->assignRole('member');
        $referrer->profile()->create(['display_name' => $referrer->name]);

        $draft = $this->createDraft();
        $interest = Interest::first();
        $goal = Goal::first();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id, 'ref' => $referrer->referral_code])
            ->set('firstName', 'Safiyyah')
            ->set('lastName', 'Referred')
            ->set('email', 'safiyyah@example.com')
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'single')
            ->set('phone', '+2348012345678')
            ->set('preferredBillingCycle', 'monthly')
            ->call('toggleInterest', $interest->slug)
            ->call('toggleGoal', $goal->slug)
            ->call('submit');

        $referred = User::query()->where('email', 'safiyyah@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $referred->referred_by);
        $this->assertSame(0, UserReferral::query()->where('referred_id', $referred->id)->count());
        $this->assertSame(0, JannahCoinsLedger::query()->where('user_id', $referrer->id)->where('reason', 'referral')->count());
    }

    public function test_unonboarded_user_is_redirected_from_home_to_membership_signup(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'HOME0001',
        ]);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $freshUser = $user->fresh();

        $this->actingAs($freshUser)
            ->get('/home')
            ->assertRedirect(route('membership.signup'));
    }
}
