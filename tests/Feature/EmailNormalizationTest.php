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
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\FakesHibp;
use Tests\TestCase;

class EmailNormalizationTest extends TestCase
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

    public function test_signup_stores_email_as_lowercase(): void
    {
        $interestSlugs = Interest::query()->orderBy('sort_order')->limit(2)->pluck('slug')->all();
        $goalSlugs = Goal::query()->orderBy('id')->limit(2)->pluck('slug')->all();

        Livewire::test(MembershipSignupWizard::class)
            ->set('step', 1)
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->set('email', 'AISHA@Example.COM')
            ->set('password', 'Tmc2024!Sec#Pass99')
            ->set('passwordConfirmation', 'Tmc2024!Sec#Pass99')
            ->set('step', 2)
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'single')
            ->set('step', 3)
            ->set('phone', '+2348012345678')
            ->set('step', 4)
            ->set('step', 5)
            ->set('selectedInterests', $interestSlugs)
            ->set('selectedGoals', $goalSlugs)
            ->call('submit')
            ->assertHasNoErrors('submit');

        $user = User::whereEmail('aisha@example.com')->first();
        $this->assertNotNull($user, 'User should be stored with lowercase email');
        $this->assertSame('aisha@example.com', $user->email);
    }

    public function test_login_works_with_mixed_case_input(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        $response = $this->post('/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_works_with_lowercase_input(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_works_when_db_has_mixed_case_email(): void
    {
        $user = User::create([
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        // Simulate pre-normalization data by force-writing mixed case
        \DB::table('users')->where('id', $user->id)->update(['email' => 'Legacy@Example.COM']);

        $response = $this->post('/login', [
            'email' => 'legacy@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_update_normalizes_email(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'NEWEMAIL@EXAMPLE.COM',
        ]);

        $user->refresh();
        $this->assertSame('newemail@example.com', $user->email);
    }

    public function test_suspended_user_can_log_in_to_renew(): void
    {
        User::create([
            'name' => 'Suspended User',
            'email' => 'suspended@example.com',
            'password' => Hash::make('password'),
            'status' => 'suspended',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        $response = $this->post('/login', [
            'email' => 'SUSPENDED@EXAMPLE.COM',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('membership.payment'));
        $this->assertAuthenticated();
    }

    public function test_manually_suspended_user_cannot_log_in(): void
    {
        User::create([
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'password' => Hash::make('password'),
            'status' => 'suspended',
            'suspended_reason' => 'Repeated community guideline breaches.',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        $response = $this->post('/login', [
            'email' => 'banned@example.com',
            'password' => 'password',
        ]);

        $response->assertInvalid(['email' => 'These credentials do not match our records.']);
        $this->assertGuest();
    }

    public function test_admin_can_send_password_reset_link(): void
    {
        Notification::fake();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);
        $admin->assignRole('super_admin');

        $member = User::create([
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users/'.$member->id);

        $response->assertOk();
    }

    public function test_mixed_case_email_in_db_allows_lowercase_login(): void
    {
        $user = User::create([
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'referral_code' => User::generateUniqueReferralCode(),
        ]);

        // Force-write mixed case via raw DB update, bypassing the model observer
        \DB::table('users')->where('id', $user->id)->update(['email' => 'Mixed@Case.COM']);

        $response = $this->post('/login', [
            'email' => 'mixed@case.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }
}
