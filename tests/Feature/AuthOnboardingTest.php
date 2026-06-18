<?php

namespace Tests\Feature;

use App\Livewire\Membership\MembershipOnboardingWizard;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\JannahCoinsLedger;
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

    public function test_user_can_register_and_is_redirected_to_membership_onboarding(): void
    {
        $response = $this->post('/register', [
            'name' => 'Aisha Member',
            'email' => 'aisha@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::query()->where('email', 'aisha@example.com')->firstOrFail();

        $response->assertRedirect(route('membership.onboarding'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('draft', $user->status);
        $this->assertNotNull($user->referral_code);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->memberProfile);
        $this->assertSame('draft', $user->memberProfile->onboarding_status);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.onboarding'));
    }

    public function test_user_can_complete_application_and_is_redirected_to_pending(): void
    {
        $user = User::query()->where('email', 'aisha@example.com')->first()
            ?? User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);

        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        if (! $user->profile) {
            $user->profile()->create(['display_name' => $user->name]);
        }

        $interestSlugs = Interest::query()->orderBy('sort_order')->limit(2)->pluck('slug')->all();
        $goalSlugs = Goal::query()->orderBy('id')->limit(2)->pluck('slug')->all();

        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->assertSet('step', 1)
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->set('nickname', 'Aishy')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('country', 'Nigeria')
            ->set('state', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'married')
            ->set('phone', '+2348000000000')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('selectedInterests', $interestSlugs)
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('selectedGoals', $goalSlugs)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->set('instagramUsername', 'aisha_m')
            ->set('xUsername', 'aisha_x')
            ->call('nextStep')
            ->assertSet('step', 6)
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $freshUser = $user->fresh();
        $this->assertEquals('pending_review', $freshUser->memberProfile->onboarding_status);
        $this->assertNotNull($freshUser->memberProfile->submitted_at);
        $this->assertEquals('pending_review', $freshUser->status);
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

        $this->post('/register', [
            'name' => 'Safiyyah',
            'email' => 'safiyyah@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'ref' => $referrer->referral_code,
        ])->assertRedirect(route('membership.onboarding'));

        $referred = User::query()->where('email', 'safiyyah@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $referred->referred_by);
        $this->assertSame(0, UserReferral::query()->where('referred_id', $referred->id)->count());
        $this->assertSame(0, JannahCoinsLedger::query()->where('user_id', $referrer->id)->where('reason', 'referral')->count());
    }

    public function test_unonboarded_user_is_redirected_from_home_to_membership_onboarding(): void
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
            ->assertRedirect(route('membership.onboarding'));
    }
}
