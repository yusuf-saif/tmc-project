<?php

namespace Tests\Feature;

use App\Livewire\Membership\MembershipSignupWizard;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\MembershipOnboardingDraft;
use App\Models\User;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipSignupWizardTest extends TestCase
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

    protected function getSignupData(): array
    {
        return [
            'firstName' => 'Aisha',
            'lastName' => 'Member',
            'email' => 'aisha@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'locationCountry' => 'Nigeria',
            'locationState' => 'Lagos',
            'ageGroup' => '25_34',
            'maritalStatus' => 'married',
            'phone' => '+2348000000000',
            'igUsername' => 'aisha_m',
            'xUsername' => 'aisha_x',
            'preferredBillingCycle' => 'monthly',
            'selectedInterests' => Interest::query()->limit(2)->pluck('slug')->all(),
            'selectedGoals' => Goal::query()->limit(2)->pluck('slug')->all(),
        ];
    }

    public function test_account_is_not_created_before_final_submit(): void
    {
        $data = $this->getSignupData();
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->assertSet('step', 1)
            ->set('firstName', $data['firstName'])
            ->set('lastName', $data['lastName'])
            ->set('email', $data['email'])
            ->set('password', $data['password'])
            ->set('passwordConfirmation', $data['passwordConfirmation'])
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('locationCountry', $data['locationCountry'])
            ->set('locationState', $data['locationState'])
            ->set('ageGroup', $data['ageGroup'])
            ->set('maritalStatus', $data['maritalStatus'])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('phone', $data['phone'])
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('igUsername', $data['igUsername'])
            ->set('xUsername', $data['xUsername'])
            ->call('nextStep')
            ->assertSet('step', 5)
            ->set('preferredBillingCycle', $data['preferredBillingCycle'])
            ->call('nextStep')
            ->assertSet('step', 6);

        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        $this->assertDatabaseMissing('member_profiles', ['onboarding_status' => 'pending_review']);
    }

    public function test_full_signup_creates_pending_review_user_and_profile(): void
    {
        $data = $this->getSignupData();
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->set('firstName', $data['firstName'])
            ->set('lastName', $data['lastName'])
            ->set('email', $data['email'])
            ->set('password', $data['password'])
            ->set('passwordConfirmation', $data['passwordConfirmation'])
            ->call('nextStep')
            ->set('locationCountry', $data['locationCountry'])
            ->set('locationState', $data['locationState'])
            ->set('ageGroup', $data['ageGroup'])
            ->set('maritalStatus', $data['maritalStatus'])
            ->call('nextStep')
            ->set('phone', $data['phone'])
            ->call('nextStep')
            ->set('igUsername', $data['igUsername'])
            ->set('xUsername', $data['xUsername'])
            ->call('nextStep')
            ->set('preferredBillingCycle', $data['preferredBillingCycle'])
            ->call('nextStep')
            ->set('selectedInterests', $data['selectedInterests'])
            ->set('selectedGoals', $data['selectedGoals'])
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending_review', $user->status);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->memberProfile);
        $this->assertEquals('pending_review', $user->memberProfile->onboarding_status);
        $this->assertEquals('Aisha', $user->memberProfile->first_name);
        $this->assertEquals('Member', $user->memberProfile->last_name);
        $this->assertEquals('Lagos', $user->memberProfile->location_state);
        $this->assertEquals('monthly', $user->memberProfile->preferred_billing_cycle);
        $this->assertNotNull($user->memberProfile->submitted_at);
    }

    public function test_password_is_hashed_and_not_stored_in_component_state(): void
    {
        $data = $this->getSignupData();
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->set('firstName', $data['firstName'])
            ->set('lastName', $data['lastName'])
            ->set('email', $data['email'])
            ->set('password', $data['password'])
            ->set('passwordConfirmation', $data['passwordConfirmation'])
            ->call('nextStep')
            ->set('locationCountry', $data['locationCountry'])
            ->set('locationState', $data['locationState'])
            ->set('ageGroup', $data['ageGroup'])
            ->set('maritalStatus', $data['maritalStatus'])
            ->call('nextStep')
            ->set('phone', $data['phone'])
            ->call('nextStep')
            ->set('igUsername', $data['igUsername'])
            ->call('nextStep')
            ->set('preferredBillingCycle', $data['preferredBillingCycle'])
            ->call('nextStep')
            ->set('selectedInterests', $data['selectedInterests'])
            ->set('selectedGoals', $data['selectedGoals'])
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertNotEquals($data['password'], $user->password);
        $this->assertTrue(Hash::check($data['password'], $user->password));

        $draft->refresh();
        $this->assertArrayNotHasKey('password', $draft->payload);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'aisha@example.com']);
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->set('email', 'aisha@example.com')
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->call('nextStep')
            ->assertHasErrors(['email']);
    }

    public function test_referral_code_is_resolved(): void
    {
        $referrer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'REFER123',
        ]);
        $referrer->assignRole('member');
        $referrer->profile()->create(['display_name' => $referrer->name]);

        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id, 'ref' => 'REFER123'])
            ->set('firstName', 'Safiyyah')
            ->set('lastName', 'Referred')
            ->set('email', 'safiyyah@example.com')
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->call('nextStep')
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'single')
            ->call('nextStep')
            ->set('phone', '+2348000000000')
            ->call('nextStep')
            ->call('nextStep')
            ->set('preferredBillingCycle', 'monthly')
            ->call('nextStep')
            ->set('selectedInterests', Interest::query()->limit(1)->pluck('slug')->all())
            ->set('selectedGoals', Goal::query()->limit(1)->pluck('slug')->all())
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $referred = User::where('email', 'safiyyah@example.com')->first();
        $this->assertNotNull($referred);
        $this->assertEquals($referrer->id, $referred->referred_by);
    }

    public function test_wizard_redirects_already_submitted_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'pending_review']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);
        MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'pending_review',
        ]);

        Livewire::actingAs($user)
            ->test(MembershipSignupWizard::class)
            ->assertRedirect(route('membership.pending'));
    }

    public function test_step_validation_blocks_invalid_advancement(): void
    {
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->assertSet('step', 1)
            ->call('nextStep')
            ->assertHasErrors(['firstName', 'lastName', 'email', 'password'])
            ->assertSet('step', 1);
    }

    public function test_billing_cycle_is_saved_to_profile(): void
    {
        $draft = $this->createDraft();

        Livewire::test(MembershipSignupWizard::class, ['draft' => $draft->id])
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->set('email', 'aisha@example.com')
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->call('nextStep')
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'married')
            ->call('nextStep')
            ->set('phone', '+2348000000000')
            ->call('nextStep')
            ->call('nextStep')
            ->set('preferredBillingCycle', 'quarterly')
            ->call('nextStep')
            ->set('selectedInterests', Interest::query()->limit(2)->pluck('slug')->all())
            ->set('selectedGoals', Goal::query()->limit(2)->pluck('slug')->all())
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $user = User::where('email', 'aisha@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('quarterly', $user->memberProfile->preferred_billing_cycle);
    }
}
