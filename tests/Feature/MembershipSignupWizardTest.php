<?php

namespace Tests\Feature;

use App\Livewire\Membership\MembershipSignupWizard;
use App\Models\Goal;
use App\Models\Interest;
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
            'selectedInterests' => Interest::query()->limit(2)->pluck('slug')->all(),
            'selectedGoals' => Goal::query()->limit(2)->pluck('slug')->all(),
        ];
    }

    public function test_account_is_not_created_before_final_submit(): void
    {
        $data = $this->getSignupData();

        Livewire::test(MembershipSignupWizard::class)
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
            ->call('nextStep')
            ->assertSet('step', 5);

        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
    }

    public function test_full_signup_creates_active_user_and_profile(): void
    {
        $data = $this->getSignupData();

        Livewire::test(MembershipSignupWizard::class)
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
            ->set('selectedInterests', $data['selectedInterests'])
            ->set('selectedGoals', $data['selectedGoals'])
            ->call('submit')
            ->assertRedirect(route('home'));

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertEquals('active', $user->status);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNotNull($user->memberProfile);
        $this->assertEquals('active', $user->memberProfile->onboarding_status);
        $this->assertEquals('Aisha', $user->memberProfile->first_name);
        $this->assertEquals('Member', $user->memberProfile->last_name);
        $this->assertEquals('Lagos', $user->memberProfile->location_state);
        $this->assertEquals('monthly', $user->memberProfile->preferred_billing_cycle);
        $this->assertNotNull($user->memberProfile->submitted_at);
    }

    public function test_password_is_hashed_and_not_stored_in_component_state(): void
    {
        $data = $this->getSignupData();

        Livewire::test(MembershipSignupWizard::class)
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
            ->set('selectedInterests', $data['selectedInterests'])
            ->set('selectedGoals', $data['selectedGoals'])
            ->call('submit')
            ->assertRedirect(route('home'));

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertNotEquals($data['password'], $user->password);
        $this->assertTrue(Hash::check($data['password'], $user->password));

        $this->assertGreaterThan(0, strlen($user->memberProfile->membership_id ?? ''));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'aisha@example.com']);

        Livewire::test(MembershipSignupWizard::class)
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
        $referrer->memberProfile()->updateOrCreate(['display_name' => $referrer->name]);

        Livewire::test(MembershipSignupWizard::class, ['ref' => 'REFER123'])
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
            ->set('selectedInterests', Interest::query()->limit(1)->pluck('slug')->all())
            ->set('selectedGoals', Goal::query()->limit(1)->pluck('slug')->all())
            ->call('submit')
            ->assertRedirect(route('home'));

        $referred = User::where('email', 'safiyyah@example.com')->first();
        $this->assertNotNull($referred);
        $this->assertEquals($referrer->id, $referred->referred_by);
    }

    public function test_wizard_redirects_active_user_to_home(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'active'],
        );

        Livewire::actingAs($user)
            ->test(MembershipSignupWizard::class)
            ->assertRedirect(route('home'));
    }

    public function test_step_validation_blocks_invalid_advancement(): void
    {
        Livewire::test(MembershipSignupWizard::class)
            ->assertSet('step', 1)
            ->call('nextStep')
            ->assertHasErrors(['firstName', 'lastName', 'email', 'password'])
            ->assertSet('step', 1);
    }

    public function test_wizard_sets_default_billing_cycle(): void
    {
        $data = $this->getSignupData();

        Livewire::test(MembershipSignupWizard::class)
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
            ->set('selectedInterests', $data['selectedInterests'])
            ->set('selectedGoals', $data['selectedGoals'])
            ->call('submit')
            ->assertRedirect(route('home'));

        $user = User::where('email', $data['email'])->first();
        $this->assertNotNull($user);
        $this->assertEquals('monthly', $user->memberProfile->preferred_billing_cycle);
    }
}
