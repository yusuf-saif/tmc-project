<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\User;
use App\Services\MembershipSignupService;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MembershipSignupServiceTest extends TestCase
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

    public function test_register_creates_user_with_correct_fields(): void
    {
        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [
                'location_country' => 'Nigeria',
                'location_state' => 'Lagos',
                'age_group' => '25_34',
                'marital_status' => 'married',
                'phone' => '+2348000000000',
                'preferred_billing_cycle' => 'monthly',
            ],
        );

        $user = $profile->user;
        $this->assertEquals('Aisha Member', $user->name);
        $this->assertEquals('aisha@example.com', $user->email);
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($profile->membership_id);
        $this->assertStringStartsWith('TMC-M-', $profile->membership_id);
        $this->assertNotNull($user->referral_code);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNotNull($user->memberProfile);
    }

    public function test_register_hashes_password(): void
    {
        $service = app(MembershipSignupService::class);

        $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [],
        );

        $user = User::where('email', 'aisha@example.com')->first();
        $this->assertNotEquals('Password123!', $user->password);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_register_syncs_interests_and_goals(): void
    {
        $interestSlugs = Interest::query()->limit(2)->pluck('slug')->all();
        $goalSlugs = Goal::query()->limit(2)->pluck('slug')->all();

        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [
                'selected_interests' => $interestSlugs,
                'selected_goals' => $goalSlugs,
            ],
        );

        $user = $profile->user;
        $this->assertCount(2, $user->interests);
        $this->assertCount(2, $user->goals);
    }

    public function test_register_sets_active_status(): void
    {
        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [],
        );

        $this->assertEquals('active', $profile->onboarding_status);
        $this->assertEquals('active', $profile->user->status);
        $this->assertNotNull($profile->activated_at);
    }

    public function test_register_assigns_member_role(): void
    {
        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [],
        );

        $this->assertTrue($profile->user->hasRole('member'));
    }

    public function test_register_resolves_referral_code(): void
    {
        $referrer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'REFER123',
        ]);
        $referrer->assignRole('member');

        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Safiyyah',
            lastName: 'Referred',
            email: 'safiyyah@example.com',
            password: 'Password123!',
            referralCode: 'REFER123',
            data: [],
        );

        $this->assertEquals($referrer->id, $profile->user->referred_by);
    }

    public function test_register_saves_billing_cycle(): void
    {
        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [
                'preferred_billing_cycle' => 'yearly',
            ],
        );

        $this->assertEquals('yearly', $profile->preferred_billing_cycle);
    }

    public function test_register_logs_user_id_on_profile(): void
    {
        $service = app(MembershipSignupService::class);

        $profile = $service->register(
            firstName: 'Aisha',
            lastName: 'Member',
            email: 'aisha@example.com',
            password: 'Password123!',
            referralCode: null,
            data: [],
        );

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $profile->user_id,
            'first_name' => 'Aisha',
            'last_name' => 'Member',
            'onboarding_status' => 'active',
        ]);
    }
}
