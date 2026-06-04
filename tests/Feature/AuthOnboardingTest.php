<?php

namespace Tests\Feature;

use App\Livewire\Onboarding\OnboardingWizard;
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

    public function test_user_can_register_complete_onboarding_and_reach_home(): void
    {
        $response = $this->post('/register', [
            'name' => 'Aisha Member',
            'email' => 'aisha@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::query()->where('email', 'aisha@example.com')->firstOrFail();

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->referral_code);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNotNull($user->profile);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('onboarding'));

        $interestIds = Interest::query()->orderBy('sort_order')->limit(2)->pluck('id')->all();
        $goalIds = Goal::query()->orderBy('id')->limit(2)->pluck('id')->all();

        Livewire::actingAs($user)
            ->test(OnboardingWizard::class)
            ->set('selectedInterests', $interestIds)
            ->call('nextStep')
            ->set('selectedGoals', $goalIds)
            ->call('nextStep')
            ->set('notificationPreferences.announcements', false)
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('enterClub')
            ->assertRedirect(route('home'));

        $user->refresh();

        $this->assertNotNull($user->profile->onboarding_completed_at);
        $this->assertDatabaseCount('jannah_coins_ledger', 1);
        $this->assertDatabaseHas('jannah_coins_ledger', [
            'user_id' => $user->id,
            'type' => 'earned',
            'reason' => 'onboarding',
            'amount' => 50,
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertOk();
    }

    public function test_onboarding_reward_is_only_awarded_once(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'MEMBER01',
        ]);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        Livewire::actingAs($user)
            ->test(OnboardingWizard::class)
            ->set('selectedInterests', Interest::query()->limit(1)->pluck('id')->all())
            ->call('nextStep')
            ->set('selectedGoals', Goal::query()->limit(1)->pluck('id')->all())
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('previousStep')
            ->call('nextStep');

        $this->assertSame(1, JannahCoinsLedger::query()->where('user_id', $user->id)->where('reason', 'onboarding')->count());
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
        ])->assertRedirect(route('home'));

        $referred = User::query()->where('email', 'safiyyah@example.com')->firstOrFail();

        $this->assertSame($referrer->id, $referred->referred_by);
        $this->assertSame(0, UserReferral::query()->where('referred_id', $referred->id)->count());
        $this->assertSame(0, JannahCoinsLedger::query()->where('user_id', $referrer->id)->where('reason', 'referral')->count());
    }

    public function test_unonboarded_user_is_redirected_from_home_to_onboarding(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'HOME0001',
        ]);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('onboarding'));
    }
}
